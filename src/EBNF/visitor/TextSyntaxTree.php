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
 * @license http://www.gnu.org/licenses/ GNU General Public License
 * @author  Sven Strittmatter <ich@weltraumschaf.de>
 * @package visitor
 */

namespace de\weltraumschaf\ebnf\visitor;

/**
 * @see Visitor
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Visitor.php';

use de\weltraumschaf\ebnf\ast\Node;
use de\weltraumschaf\ebnf\ast\Composite;
use de\weltraumschaf\ebnf\ast\Identifier;
use de\weltraumschaf\ebnf\ast\Rule;
use de\weltraumschaf\ebnf\ast\Terminal;

/**
 * @package visitor
 */
class TextSyntaxTree implements Visitor {

    const DEFAULT_INDENTATION = 4;

    private $text = "";
    private $indentationLevel = 0;

    private function indent() {
        return str_repeat(" ", $this->indentationLevel * self::DEFAULT_INDENTATION);
    }

    public function beforeVisit(Node $visitable) {
        // do nothing here.
    }

    public function visit(Node $visitable) {
        $this->text .= "{$this->indent()}[{$visitable->getNodeName()}";

        if ($visitable instanceof Rule) {
            $this->text .= "='{$visitable->name}'";
        } else if ($visitable instanceof Terminal || $visitable instanceof Identifier) {
            $this->text .= "='{$visitable->value}'";
        }

        if ($visitable instanceof Composite) {
            $this->indentationLevel++;
        }

        $this->text .= "]" . PHP_EOL;
    }

    public function afterVisit(Node $visitable) {
        if ($visitable instanceof Composite) {
            $this->indentationLevel--;
        }
    }

    public function getText() {
        return $this->text;
    }

}