<?php
require_once __DIR__.'/../VisualSubtree.php';
require_once __DIR__.'/../VisualPreparationSubtree.php';
require_once __DIR__.'/../VisualPreparationNode.php';
require_once __DIR__.'/../VisualLine.php';
require_once 'JSONImg.php';
require_once 'JSONLink.php';
require_once 'JSONLine.php';
require_once 'JSONConnection.php';
require_once 'JSONCircle.php';

class JSONSubtree extends VisualSubtree {
    protected function instantiateVisualSubtree (VisualPreparationSubtree $subtree): VisualSubtree {
        return new JSONSubtree($subtree);
    }
    protected function createNodeIcon (VisualPreparationNode $node): VisualLink {
        $icon = new JSONImg($node);
        return new JSONLink($node, $icon);
    }
    protected function instantiateVisualLine (int $x1, int $y1, int $x2, int $y2, int $groupId): VisualLine {
        return new JSONLine($x1, $y1, $x2, $y2, $groupId);
    }
    protected function instantiateVisualConnection (VisualLine $line,
            int $groupId, string $text = null): VisualConnection {
        return new JSONConnection($line, $groupId, $text);
    }
    protected function instantiateVisualCircle (int $x, int $y,
            int $r, string $color, VisualPreparationNode $node): VisualCircle {
        return new JSONCircle($x, $y, $r, $color, $node);
    }

    public function getLogInfo (): string {
        return 'JSONSubtree;;';
    }
}