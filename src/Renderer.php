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

use \DOMDocument              as DOMDocument;
use \DOMElement               as DOMElement;
use \RuntimeException         as RuntimeException;
use \InvalidArgumentException as InvalidArgumentException;

/**
 * Renders the XML DOM syntax tree generated by the Parser} to different formats.
 *
 * Either you can choose between some image formats (png, gif, jpg) or XML. The XML
 * format is the direct syntax tree representation generated by the parser.
 */
class Renderer {
    const DEFAULT_FONT = 4;
    const DEFAULT_UNIT = 16;

    const FORMAT_PNG = "png";
    const FORMAT_JPG = "jpg";
    const FORMAT_GIF = "gif";
    const FORMAT_XML = "xml";

    /**
     * GD lib color resource for white.
     *
     * @var resource
     */
    private $white;
    /**
     * GD lib color resource for black.
     *
     * @var resource
     */
    private $black;
    /**
     * GD lib color resource for blue.
     *
     * @var resource
     */
    private $blue;
    /**
     * GD lib color resource for red.
     *
     * @var resource
     */
    private $red;
    /**
     * GD lib color resource for green.
     *
     * @var resource
     */
    private $green;
    /**
     * GD lib color resource for silver.
     *
     * @var resource
     */
    private $silver;
    /**
     * The output format.
     *
     * @var string
     */
    private $format;
    /**
     * The output file.
     *
     * @var string
     */
    private $file;
    /**
     * The syntax tree.
     *
     * @var DOMDocument
     */
    private $dom;
    private $font;
    private $unit;

    /**
     * Initializes the renderer with the syntax tree, format and file.
     *
     * Optional you can specify the GD lib font siye and the unit.
     *
     * @param string      $format
     * @param string      $file
     * @param DOMDocument $dom
     * @param int         $font
     * @param int         $unit
     */
    public function __construct($format, $file, DOMDocument $dom, $font = self::DEFAULT_FONT, $unit = self::DEFAULT_UNIT) {
        $this->format = (string)$format;
        $this->file   = (string)$file;
        $this->dom    = $dom;
        $this->font   = (int)$font;
        $this->unit   = (int)$unit;
    }

    /**
     * renders ans saves the grammar into file.
     */
    public function save() {
        if (self::FORMAT_XML === $this->format) {
            $out = $this->dom->saveXML();

            if (false === file_put_contents($this->file, $out)) {
                throw new \RuntimeException("Can't write output to '{$this->file}'!");
            }
        } else {
            $this->saveImage();
        }
    }

    /**
     * Render and saves especialy all image formats.
     */
    private function saveImage() {
        $out = $this->renderNode($this->dom->firstChild, true);

        switch ($this->format) {
            case self::FORMAT_PNG:
                imagepng($out, $this->file);
                break;
            case self::FORMAT_JPG:
                imagejpeg($out, $this->file);;
                break;
            case self::FORMAT_GIF:
                imagegif($out, $this->file);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported format: '{$this->format}'!");
        }

        imagedestroy($out);
    }

    private function rr($im, $x1, $y1, $x2, $y2, $r, $black) {
        imageline($im, $x1 + $r, $y1, $x2 - $r, $y1, $black);
        imageline($im, $x1 + $r, $y2, $x2 - $r, $y2, $black);
        imageline($im, $x1, $y1 + $r, $x1, $y2 - $r, $black);
        imageline($im, $x2, $y1 + $r, $x2, $y2 - $r, $black);
        imagearc($im, $x1 + $r, $y1 + $r, 2 * $r, 2 * $r, 180, 270, $black);
        imagearc($im, $x2 - $r, $y1 + $r, 2 * $r, 2 * $r, 270, 360, $black);
        imagearc($im, $x1 + $r, $y2 - $r, 2 * $r, 2 * $r, 90, 180, $black);
        imagearc($im, $x2 - $r, $y2 - $r, 2 * $r, 2 * $r, 0, 90, $black);
    }

    /**
     *
     * @param int $width
     * @param int $height
     * @return resource
     */
    private function createImage($width, $height) {
        $im = imagecreatetruecolor($width, $height);
        imageantialias($im, true);
        $this->white  = imagecolorallocate($im, 255, 255, 255);
        $this->black  = imagecolorallocate($im, 0, 0, 0);
        $this->blue   = imagecolorallocate($im, 0, 0, 255);
        $this->red    = imagecolorallocate($im, 255, 0, 0);
        $this->green  = imagecolorallocate($im, 0, 200, 0);
        $this->silver = imagecolorallocate($im, 127, 127, 127);
        imagefilledrectangle($im, 0, 0, $width, $height, $this->white);

        return $im;
    }

    private function arrow($image, $x, $y, $lefttoright) {
        if ($lefttoright) {
            $points = array(
                $x - $this->unit,
                $y - $this->unit / 3,
                $x,
                $y,
                $x - $this->unit,
                $y + $this->unit / 3
            );
        } else {
            $points = array(
                $x,
                $y - $this->unit / 3,
                $x - $this->unit,
                $y,
                $x,
                $y + $this->unit / 3);
        }

        imagefilledpolygon($image, $points, 3, $this->black);
    }

    /**
     *
     * @param DOMElement $node
     * @param bool $leftToRight
     * @return resource
     */
    private function renderNode(DOMElement $node, $leftToRight) {
        if ($node->nodeName === Parser::NODE_TYPE_IDENTIFIER || $node->nodeName === Parser::NODE_TYPE_TERMINAL) {
            return $this->renderIdentifierOrTerminal($node, $leftToRight);
        } else if ($node->nodeName === Parser::NODE_TYPE_OPTION || $node->nodeName === Parser::NODE_TYPE_LOOP) {
            return $this->renderOptionOrLoopNode($node, $leftToRight);
        } else if ($node->nodeName === Parser::NODE_TYPE_SEQUENCE) {
            return $this->renderSequenceNode($node, $leftToRight);
        } else if ($node->nodeName === Parser::NODE_TYPE_CHOISE) {
            return $this->renderChoiseNode($node, $leftToRight);
        } else if ($node->nodeName === Parser::NODE_TYPE_SYNTAX) {
            return $this->renderSyntaxNode($node, $leftToRight);
        }
    }

    private function renderChilds($node, $lefttoright) {
        $childs = array();
        $node = $node->firstChild;

        while ($node !== null) {
            $childs[] = $this->renderNode($node, $lefttoright);
            $node = $node->nextSibling;
        }

        return $childs;
    }

    private function renderSyntaxNode($node, $leftToRight) {
        $title  = $node->getAttribute('title');
        $meta   = $node->getAttribute('meta');
        $node   = $node->firstChild;
        $names  = array();
        $images = array();

        while ($node != null) {
            $names[]  = $node->getAttribute('name');
            $im       = $this->renderNode($node->firstChild, $leftToRight);
            $images[] = $im;
            $node     = $node->nextSibling;
        }

        $wn = 0;
        $wr = 0;
        $h  = 5 * $this->unit;

        for ($i = 0; $i < count($images); $i++) {
            $wn = max($wn, imagefontwidth($this->font) * strlen($names[$i]));
            $wr = max($wr, imagesx($images[$i]));
            $h += imagesy($images[$i]) + 2 * $this->unit;
        }

        if ($title == '') {
            $h -= 2 * $this->unit;
        }

        if ($meta == '') {
            $h -= 2 * $this->unit;
        }

        $h += 10;
        $w  = max($wr + $wn + 3 * $this->unit, imagefontwidth(1) * strlen($meta) + 2 * $this->unit) + 10;
        $im = $this->createImage($w, $h);
        $y  = 2 * $this->unit;

        if ($title != '') {
            imagestring($im, $this->font, $this->unit, (2 * $this->unit - imagefontheight($this->font)) / 2, $title, $this->green);
            imageline($im, 5, 2 * $this->unit, $w - 5, 2 * $this->unit, $this->green);
            $y += 2 * $this->unit;
        }

        for ($i = 0; $i < count($images); $i++) {
            imagestring($im, $this->font, $this->unit, $y - $this->unit + (2 * $this->unit - imagefontheight($this->font)) / 2, $names[$i], $this->red);
            imagecopy($im, $images[$i], $wn + 2 * $this->unit, $y, 0, 0, imagesx($images[$i]), imagesy($images[$i]));
            imageline($im, $this->unit, $y + $this->unit, $wn + 2 * $this->unit, $y + $this->unit, $this->black);
            imageline($im, $wn + 2 * $this->unit + imagesx($images[$i]) - 1, $y + $this->unit, $w - $this->unit, $y + $this->unit, $this->black);
            imageline($im, $w - $this->unit, $y + $this->unit / 2, $w - $this->unit, $y + 1.5 * $this->unit, $this->black);
            $y += 2 * $this->unit + imagesy($images[$i]);
        }

        imagestring($im, 1, $this->unit, $h - 2 * $this->unit + (2 * $this->unit - imagefontheight(1)) / 2, $meta, $this->silver);
        $this->rr($im, 5, 5, $w - 5, $h - 5, $this->unit / 2, $this->green);

        return $im;
    }

    private function renderChoiseNode($node, $leftToRight) {
        $inner = $this->renderChilds($node, $leftToRight);
        $h = (count($inner) - 1) * $this->unit;
        $w = 0;

        for ($i = 0; $i < count($inner); $i++) {
            $h += imagesy($inner[$i]);
            $w = max($w, imagesx($inner[$i]));
        }

        $w += 6 * $this->unit;
        $im = $this->createImage($w, $h);
        $y = 0;
        imageline($im, 0, $this->unit, $this->unit, $this->unit, $this->black);
        imageline($im, $w - $this->unit, $this->unit, $w, $this->unit, $this->black);

        for ($i = 0; $i < count($inner); $i++) {
            imageline($im, $this->unit, $y + $this->unit, $w - $this->unit, $y + $this->unit, $this->black);
            imagecopy($im, $inner[$i], 3 * $this->unit, $y, 0, 0, imagesx($inner[$i]), imagesy($inner[$i]));
            $this->arrow($im, 3 * $this->unit, $y + $this->unit, $leftToRight);
            $this->arrow($im, $w - 2 * $this->unit, $y + $this->unit, $leftToRight);
            $top = $y + $this->unit;
            $y += imagesy($inner[$i]) + $this->unit;
        }

        imageline($im, $this->unit, $this->unit, $this->unit, $top, $this->black);
        imageline($im, $w - $this->unit, $this->unit, $w - $this->unit, $top, $this->black);

        return $im;
    }

    private function renderSequenceNode($node, $leftToRight) {
        $inner = $this->renderChilds($node, $leftToRight);

        if (!$leftToRight) {
            $inner = array_reverse($inner);
        }

        $w = count($inner) * $this->unit - $this->unit;
        $h = 0;

        for ($i = 0; $i < count($inner); $i++) {
            $w += imagesx($inner[$i]);
            $h = max($h, imagesy($inner[$i]));
        }

        $im = $this->createImage($w, $h);
        imagecopy($im, $inner[0], 0, 0, 0, 0, imagesx($inner[0]), imagesy($inner[0]));
        $x = imagesx($inner[0]) + $this->unit;

        for ($i = 1; $i < count($inner); $i++) {
            imageline($im, $x - $this->unit - 1, $this->unit, $x, $this->unit, $this->black);
            $this->arrow($im, $x, $this->unit, $leftToRight);
            imagecopy($im, $inner[$i], $x, 0, 0, 0, imagesx($inner[$i]), imagesy($inner[$i]));
            $x += imagesx($inner[$i]) + $this->unit;
        }

        return $im;
    }

    private function renderOptionOrLoopNode($node, $leftToRight) {
        if ($node->nodeName === Parser::NODE_TYPE_LOOP) {
            $leftToRight = !$leftToRight;
        }

        $inner = $this->renderNode($node->firstChild, $leftToRight);
        $w = imagesx($inner) + 6 * $this->unit;
        $h = imagesy($inner) + 2 * $this->unit;
        $im = $this->createImage($w, $h);
        imagecopy($im, $inner, 3 * $this->unit, 2 * $this->unit, 0, 0, imagesx($inner), imagesy($inner));
        imageline($im, 0, $this->unit, $w, $this->unit, $this->black);

        if ($node->nodeName === Parser::NODE_TYPE_LOOP) {
            $this->arrow($im, $w / 2 + $this->unit / 2, $this->unit, !$leftToRight);
        } else {
            $this->arrow($im, $w / 2 + $this->unit / 2, $this->unit, $leftToRight);
        }

        $this->arrow($im, 3 * $this->unit, 3 * $this->unit, $leftToRight);
        $this->arrow($im, $w - 2 * $this->unit, 3 * $this->unit, $leftToRight);
        imageline($im, $this->unit, $this->unit, $this->unit, 3 * $this->unit, $this->black);
        imageline($im, $this->unit, 3 * $this->unit, 2 * $this->unit, 3 * $this->unit, $this->black);
        imageline($im, $w - $this->unit, $this->unit, $w - $this->unit, 3 * $this->unit, $this->black);
        imageline($im, $w - 3 * $this->unit - 1, 3 * $this->unit, $w - $this->unit, 3 * $this->unit, $this->black);

        return $im;
    }

    private function renderIdentifierOrTerminal($node, $leftToRight) {
        $text = $node->getAttribute('value');
        $w = imagefontwidth($this->font) * (strlen($text)) + 4 * $this->unit;
        $h = 2 * $this->unit;
        $im = $this->createImage($w, $h);

        if ($node->nodeName !== Parser::NODE_TYPE_TERMINAL) {
            imagerectangle($im, $this->unit, 0, $w - $this->unit - 1, $h - 1, $this->black);
            imagestring($im, $this->font, 2 * $this->unit, ($h - imagefontheight($this->font)) / 2, $text, $this->red);
        } else {
            if ($text !== "...") {
                $this->rr($im, $this->unit, 0, $w - $this->unit - 1, $h - 1, $this->unit / 2, $this->black);
            }

            if ($text !== "...") {
                $color = $this->blue;
            } else {
                $color = $this->black;
            }

            imagestring($im, $this->font, 2 * $this->unit, ($h - imagefontheight($this->font)) / 2, $text, $color);
        }

        imageline($im, 0, $this->unit, $this->unit, $this->unit, $this->black);
        imageline($im, $w - $this->unit, $this->unit, $w + 1, $this->unit, $this->black);

        return $im;
    }
}
