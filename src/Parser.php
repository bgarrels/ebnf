<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Vincent Tscherter <tscherter@karmin.ch>
 * @author Sven Strittmatter <ich@weltraumschaf.de>
 */

namespace de\weltraumschaf\ebnf;

use \DOMDocument as DOMDocument;
use \DOMElement  as DOMElement;

/**
 * @see {Token}
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Token.php';
/**
 * @see {SyntaxtException}
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'SyntaxtException.php';
/**
 * @see {Position}
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Position.php';

/**
 * Parses a stream of EBNF tokens and generate a XML DOM tree.
 *
 * This class provides only one public method which provides returns
 * the syntax tree as XML DOM tree.
 *
 * @todo Use objects of type Token instead of strings.
 * @todo Throw SyntaxtException with propper positions.
 */
class Parser {
    const META = "xis/ebnf v2.0 http://wiki.karmin.ch/ebnf/ gpl3";

    const NODE_TYPE_CHOICE     = "choice";
    const NODE_TYPE_IDENTIFIER = "identifier";
    const NODE_TYPE_LOOP       = "loop";
    const NODE_TYPE_OPTION     = "option";
    const NODE_TYPE_RULE       = "rule";
    const NODE_TYPE_SEQUENCE   = "sequence";
    const NODE_TYPE_SYNTAX     = "syntax";
    const NODE_TYPE_TERMINAL   = "terminal";

    /**
     * Used to receive the tokens.
     *
     * @var Scanner
     */
    private $scanner;
    /**
     * @var DOMDocument
     */
    private $dom;

    /**
     * Initialized with a scanner which produced the token stream.
     *
     * @param Scanner $scanner
     */
    public function __construct(Scanner $scanner) {
        $this->scanner = $scanner;
        $this->dom     = new DOMDocument();
    }

    /**
     * Parses the EBNF tokens and returns a syntax tree as DOMDocument object on success.
     *
     * On semantic syntax errors a SyntaxtException will be thrown.
     *
     * @throws SyntaxtException
     * @return DOMDocument
     */
    public function parse() {
        $syntax = $this->dom->createElement(self::NODE_TYPE_SYNTAX);
        $this->scanner->nextToken();

        if ($this->scanner->currentToken()->isType(Token::LITERAL)) {
            $syntax->setAttribute('title', $this->scanner->currentToken()->getValue(true));
            $this->scanner->nextToken();
        }

        if (!$this->assertToken($this->scanner->currentToken(), Token::OPERATOR, '{')) {
            throw new SyntaxtException("Syntax must start with '{'", $this->scanner->currentToken()->getPosition());
        }

        $this->scanner->nextToken();

        while ($this->scanner->hasNextToken() && $this->scanner->currentToken()->isType(Token::IDENTIFIER)) {
            $syntax->appendChild($this->parseProduction());
            $this->scanner->nextToken();
        }

        if (!$this->assertToken($this->scanner->currentToken(), Token::OPERATOR, '}')) {
            throw new SyntaxtException("Syntax must end with '}'", $this->scanner->currentToken()->getPosition());
        }

        $this->scanner->nextToken();

        if ($this->scanner->hasNextToken()) {
            if ($this->scanner->currentToken()->isType(Token::LITERAL)) {
                $syntax->setAttribute('meta', $this->scanner->currentToken()->getValue(true));
            } else {
                throw new SyntaxtException("Literal expected as syntax comment", $this->scanner->currentToken()->getPosition());
            }
        } else {
            $syntax->setAttribute('meta', self::META);
        }

        $this->dom->appendChild($syntax);
        return $this->dom;
    }

    /**
     * Parses an EBNF production: rule = identifier ( "=" | ":==" | ":" ) expression ( "." | ";" ) .
     *
     * @throws SyntaxtException
     * @return DOMElement
     */
    private function parseProduction() {
        if (!$this->scanner->currentToken()->isType(Token::IDENTIFIER)) {
            throw new SyntaxtException("Production must start with an identifier", $this->scanner->currentToken()->getPosition());
        }

        $production = $this->dom->createElement(self::NODE_TYPE_RULE);
        $production->setAttribute('name', $this->scanner->currentToken()->getValue());
        $this->scanner->nextToken();

        if (!$this->assertTokens($this->scanner->currentToken(), Token::OPERATOR, array("=", ":", ":=="))) {
            throw new SyntaxtException("Identifier must be followed by '='", $this->scanner->currentToken()->getPosition());
        }

        $this->scanner->nextToken();
        $production->appendChild($this->parseExpression());

        if (!$this->assertTokens($this->scanner->currentToken(), Token::OPERATOR, array(".", ";"))) {
            throw new SyntaxtException("Rule must end with '.' or ';'", $this->scanner->backtrackToken(2)->getPosition(true));
        }

        return $production;
    }

    /**
     * Parses an EBNF expression: expression = term { "|" term } .
     *
     * @throws SyntaxtException
     * @return DOMElement
     */
    private function parseExpression() {
        $choice = $this->dom->createElement(self::NODE_TYPE_CHOICE);
        $choice->appendChild($this->parseTerm());
        $mul = false;

        while ($this->assertToken($this->scanner->currentToken(), Token::OPERATOR, '|')) {
            $this->scanner->nextToken();
            $choice->appendChild($this->parseTerm());
            $mul   = true;
        }

        if ($mul) {
            return $choice;
        }

        return $choice->removeChild($choice->firstChild);
    }

    /**
     * Parses an EBNF term: term = factor { factor } .
     *
     * @throws SyntaxtException
     * @return DOMElement
     */
    private function parseTerm() {
        $sequence = $this->dom->createElement(self::NODE_TYPE_SEQUENCE);
        $sequence->appendChild($this->parseFactor());
        $this->scanner->nextToken();
        $mul   = false;

        while ($this->scanner->currentToken()->isNotEquals(array('.', '=', '|', ')', ']', '}'))) {
            $sequence->appendChild($this->parseFactor());
            $this->scanner->nextToken();
            $mul = true;
        }

        if ($mul) {
            return $sequence;
        }

        return $sequence->removeChild($sequence->firstChild);
    }

    /**
     * Parses an EBNF factor:
     * factor = identifier
     *        | literal
     *        | "[" expression "]"
     *        | "(" expression ")"
     *        | "{" expression "}" .
     *
     * @throws SyntaxtException
     * @return DOMElement
     */
    private function parseFactor() {
        if ($this->scanner->currentToken()->isType(Token::IDENTIFIER)) {
            $identifier = $this->dom->createElement(self::NODE_TYPE_IDENTIFIER);
            $identifier->setAttribute('value', $this->scanner->currentToken()->getValue());

            return $identifier;
        }

        if ($this->scanner->currentToken()->isType(Token::LITERAL)) {
            $literal = $this->dom->createElement(self::NODE_TYPE_TERMINAL);
            $literal->setAttribute('value', $this->scanner->currentToken()->getValue(true));

            return $literal;
        }

        if ($this->assertToken($this->scanner->currentToken(), Token::OPERATOR, '(')) {
            $this->scanner->nextToken();
            $expression = $this->parseExpression();

            if (!$this->assertToken($this->scanner->currentToken(), Token::OPERATOR, ')')) {
                throw new SyntaxtException("Group must end with ')'", $this->scanner->currentToken()->getPosition());
            }

            return $expression;
        }

        if ($this->assertToken($this->scanner->currentToken(), Token::OPERATOR, '[')) {
            $option = $this->dom->createElement(self::NODE_TYPE_OPTION);
            $this->scanner->nextToken();
            $option->appendChild($this->parseExpression());

            if (!$this->assertToken($this->scanner->currentToken(), Token::OPERATOR, ']')) {
                throw new SyntaxtException("Option must end with ']'", $this->scanner->currentToken()->getPosition());
            }

            return $option;
        }

        if ($this->assertToken($this->scanner->currentToken(), Token::OPERATOR, '{')) {
            $loop = $this->dom->createElement(self::NODE_TYPE_LOOP);
            $this->scanner->nextToken();
            $loop->appendChild($this->parseExpression());

            if (!$this->assertToken($this->scanner->currentToken(), Token::OPERATOR, '}')) {
                throw new SyntaxtException("Loop must end with '}'", $this->scanner->currentToken()->getPosition());
            }

            return $loop;
        }

        throw new SyntaxtException("Factor expected", $this->scanner->currentToken()->getPosition());
    }

    /**
     * Checks wheter a token is of a type and is equalt to a string literal or not.
     *
     * @param Token $token
     * @param int $type
     * @param string $value
     * @return bool
     */
    protected function assertToken(Token $token, $type, $value) {
        return $token->isType($type) && $token->isEqual($value);
    }

    /**
     * Checks wheter a token is of a type and is equalt to a array of string literasl or not.
     *
     * @param Token $token
     * @param int $type
     * @param array $value Array of strings.
     * @return bool
     */
    protected  function assertTokens(Token $token, $type, array $values) {
        foreach ($values as $value) {
            if ($this->assertToken($token, $type, $value)) {
                return true;
            }
        }

        return false;
    }
}
