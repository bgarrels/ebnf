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

namespace Weltraumschaf\Ebnf;

require_once "Scanner.php";

/**
 * Testcase for class Scanner.
 */
class ScannerTest extends \PHPUnit_Framework_TestCase {

    private $ops = array("(", ")", "[", "]", "{", "}", "=", ".", ";", "|");
    private $lowAlpha = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r",
        "s", "t", "u", "v", "w", "x", "y", "z");
    private $upAlpha = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z");
    private $nums = array("1", "2", "3", "4", "5",  "6",  "7",  "8",  "9", "0");
    private $ws = array(" ", "\n", "\r", "\t");

    public function testIsAlpha() {
        foreach ($this->lowAlpha as $c) {
            $this->assertTrue(Scanner::isAlpha($c), $c);
        }

        foreach ($this->upAlpha as $c) {
            $this->assertTrue(Scanner::isAlpha($c), $c);
        }

        foreach ($this->nums as $c) {
            $this->assertFalse(Scanner::isAlpha($c), $c);
        }

        foreach ($this->ops as $c) {
            $this->assertFalse(Scanner::isAlpha($c), $c);
        }

        foreach ($this->ws as $c) {
            $this->assertFalse(Scanner::isAlpha($c), $c);
        }
    }

    public function testIsNum() {
        foreach ($this->nums as $c) {
            $this->assertTrue(Scanner::isNum($c), $c);
        }

        foreach ($this->ops as $c) {
            $this->assertFalse(Scanner::isNum($c), $c);
        }

        foreach ($this->lowAlpha as $c) {
            $this->assertFalse(Scanner::isNum($c), $c);
        }

        foreach ($this->upAlpha as $c) {
            $this->assertFalse(Scanner::isNum($c), $c);
        }

        foreach ($this->ws as $c) {
            $this->assertFalse(Scanner::isNum($c), $c);
        }
    }

    public function testIsAlphaNum() {
        foreach ($this->nums as $c) {
            $this->assertTrue(Scanner::isAlphaNum($c), $c);
        }

        foreach ($this->lowAlpha as $c) {
            $this->assertTrue(Scanner::isAlphaNum($c), $c);
        }

        foreach ($this->upAlpha as $c) {
            $this->assertTrue(Scanner::isAlphaNum($c), $c);
        }

        foreach ($this->ops as $c) {
            $this->assertFalse(Scanner::isAlphaNum($c), $c);
        }

        foreach ($this->ws as $c) {
            $this->assertFalse(Scanner::isAlphaNum($c), $c);
        }
    }

    public function testIsOperator() {
        foreach ($this->ops as $c) {
            $this->assertTrue(Scanner::isOperator($c), $c);
        }

        foreach ($this->nums as $c) {
            $this->assertFalse(Scanner::isOperator($c), $c);
        }

        foreach ($this->lowAlpha as $c) {
            $this->assertFalse(Scanner::isOperator($c), $c);
        }

        foreach ($this->upAlpha as $c) {
            $this->assertFalse(Scanner::isOperator($c), $c);
        }

        foreach ($this->ws as $c) {
            $this->assertFalse(Scanner::isOperator($c), $c);
        }
    }

    public function testIsWhiteSpace() {
        foreach ($this->ws as $c) {
            $this->assertTrue(Scanner::isWhiteSpace($c), $c);
        }

        foreach ($this->ops as $c) {
            $this->assertFalse(Scanner::isWhiteSpace($c), $c);
        }

        foreach ($this->nums as $c) {
            $this->assertFalse(Scanner::isWhiteSpace($c), $c);
        }

        foreach ($this->lowAlpha as $c) {
            $this->assertFalse(Scanner::isWhiteSpace($c), $c);
        }

        foreach ($this->upAlpha as $c) {
            $this->assertFalse(Scanner::isWhiteSpace($c), $c);
        }
    }

    public function testIsQuote() {
        $this->assertTrue(Scanner::isQuote('"'));
        $this->assertTrue(Scanner::isQuote("'"));

        foreach ($this->ws as $c) {
            $this->assertFalse(Scanner::isQuote($c), $c);
        }

        foreach ($this->ops as $c) {
            $this->assertFalse(Scanner::isQuote($c), $c);
        }

        foreach ($this->nums as $c) {
            $this->assertFalse(Scanner::isQuote($c), $c);
        }

        foreach ($this->lowAlpha as $c) {
            $this->assertFalse(Scanner::isQuote($c), $c);
        }

        foreach ($this->upAlpha as $c) {
            $this->assertFalse(Scanner::isQuote($c), $c);
        }
    }

    public function testNext() {
$g = <<<EOD
title      = literal .
comment    = literal .
EOD;
        $expectations = array(
            array("value" => "title",   "type" => Token::IDENTIFIER, "line" => 1, "col" => 1),
            array("value" => "=",       "type" => Token::OPERATOR,   "line" => 1, "col" => 12),
            array("value" => "literal", "type" => Token::IDENTIFIER, "line" => 1, "col" => 14),
            array("value" => ".",       "type" => Token::OPERATOR,   "line" => 1, "col" => 22),
            array("value" => "comment", "type" => Token::IDENTIFIER, "line" => 2, "col" => 1),
            array("value" => "=",       "type" => Token::OPERATOR,   "line" => 2, "col" => 12),
            array("value" => "literal", "type" => Token::IDENTIFIER, "line" => 2, "col" => 14),
            array("value" => ".",       "type" => Token::OPERATOR,   "line" => 2, "col" => 22),
            array("value" => "",        "type" => Token::EOF,        "line" => 2, "col" => 22),
        );

        $s = new Scanner(trim($g));
        $cnt = 0;

        while ($s->hasNextToken()) {
            $s->nextToken();
            $t = $s->currentToken();
            $e = $expectations[$cnt];
            $this->assertInstanceOf("Weltraumschaf\Ebnf\Token", $t, $cnt);
            $this->assertEquals($e["type"], $t->getType(), $cnt);
            $this->assertEquals($e["value"], $t->getValue(), $cnt);
            $p = $t->getPosition();
            $this->assertInstanceOf("Weltraumschaf\Ebnf\Position", $p, $cnt);
            $this->assertNull($p->getFile(), $cnt);
            $this->assertEquals($e["line"], $p->getLine(), $cnt);
            $this->assertEquals($e["col"], $p->getColumn(), $cnt);
            $cnt++;
        }

        $this->assertEquals(count($expectations), $cnt, "Not enough tokens!");

$g = <<<EOD
literal = "'" character { character } "'"
        | '"' character { character } '"' .
EOD;

        $expectations = array(
            array("value" => "literal",   "type" => Token::IDENTIFIER, "line" => 1, "col" => 1),
            array("value" => "=",         "type" => Token::OPERATOR,   "line" => 1, "col" => 9),
            array("value" => '"\'"',      "type" => Token::LITERAL,    "line" => 1, "col" => 11),
            array("value" => "character", "type" => Token::IDENTIFIER, "line" => 1, "col" => 15),
            array("value" => "{",         "type" => Token::OPERATOR,   "line" => 1, "col" => 25),
            array("value" => "character", "type" => Token::IDENTIFIER, "line" => 1, "col" => 27),
            array("value" => "}",         "type" => Token::OPERATOR,   "line" => 1, "col" => 37),
            array("value" => '"\'"',      "type" => Token::LITERAL,    "line" => 1, "col" => 39),
            array("value" => "|",         "type" => Token::OPERATOR,   "line" => 2, "col" => 9),
            array("value" => "'\"'",      "type" => Token::LITERAL,    "line" => 2, "col" => 11),
            array("value" => "character", "type" => Token::IDENTIFIER, "line" => 2, "col" => 15),
            array("value" => "{",         "type" => Token::OPERATOR,   "line" => 2, "col" => 25),
            array("value" => "character", "type" => Token::IDENTIFIER, "line" => 2, "col" => 27),
            array("value" => "}",         "type" => Token::OPERATOR,   "line" => 2, "col" => 37),
            array("value" => "'\"'",      "type" => Token::LITERAL,    "line" => 2, "col" => 39),
            array("value" => ".",         "type" => Token::OPERATOR,   "line" => 2, "col" => 43),
            array("value" => "",          "type" => Token::EOF,        "line" => 2, "col" => 43),
        );

        $s = new Scanner(trim($g));
        $cnt = 0;

        while ($s->hasNextToken()) {
            $s->nextToken();
            $t = $s->currentToken();
            $e = $expectations[$cnt];
            $this->assertInstanceOf("Weltraumschaf\Ebnf\Token", $t, $cnt);
            $this->assertEquals($e["type"], $t->getType(), $cnt);
            $this->assertEquals($e["value"], $t->getValue(), $cnt);
            $p = $t->getPosition();
            $this->assertInstanceOf("Weltraumschaf\Ebnf\Position", $p, $cnt);
            $this->assertNull($p->getFile(), $cnt);
            $this->assertEquals($e["line"], $p->getLine(), $cnt);
            $this->assertEquals($e["col"], $p->getColumn(), $cnt);
            $cnt++;
        }

        $this->assertEquals(count($expectations), $cnt, "Not enough tokens!");
    }
}