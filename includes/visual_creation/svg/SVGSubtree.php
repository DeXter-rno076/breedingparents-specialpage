<?php
require_once __DIR__.'/../VisualSubtree.php';
require_once __DIR__.'/../VisualPreparationNode.php';
require_once __DIR__.'/../VisualLine.php';
require_once 'SVGImg.php';
require_once 'SVGLink.php';
require_once 'SVGLine.php';
require_once 'SVGConnection.php';
require_once 'SVGCircle.php';


class SVGSubtree extends VisualSubtree {
    protected function instantiateVisualSubtree (VisualPreparationSubtree $subtree): VisualSubtree {
        return new SVGSubtree($subtree);
    }
    protected function createNodeIcon (VisualPreparationNode $node): VisualLink {
        $icon = new SVGImg($node);
        return new SVGLink($node, $icon);
    }
    protected function instantiateVisualLine (int $x1, int $y1, int $x2, int $y2, int $groupId): VisualLine {
        return new SVGLine($x1, $y1, $x2, $y2, $groupId);
    }
    protected function instantiateVisualConnection (VisualLine $line,
            int $groupId, string $text = null): VisualConnection {
        return new SVGConnection($line, $groupId, $text);
    }
    protected function instantiateVisualCircle (int $x, int $y,
            int $r, string $color, VisualPreparationNode $node): VisualCircle {
        return new SVGCircle($x, $y, $r, $color, $node);
    }

    public function getLogInfo (): string {
        return 'SVGSubtree;;';
    }
}