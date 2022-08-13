<?php
require_once __DIR__.'/../VisualLine.php';
require_once 'SVGElement.php';
require_once __DIR__.'/../../HTMLElement.php';

class SVGLine extends VisualLine implements SVGElement {
    public function compile (int $xOffset, int $yOffset): HTMLElement {
        $line = new HTMLElement('line', [
            'x1' => $this->x1 + $xOffset,
            'y1' => $this->y1 + $yOffset,
            'x2' => $this->x2 + $xOffset,
            'y2' => $this->y2 + $yOffset,
            'groupid' => $this->groupId
        ]);

        return $line;
    }

    public function getLogInfo (): string {
        return '\'\'\'SVGLine\'\'\':('.$this->x1.';'.$this->y1.')->('.$this->x2.';'.$this->y2.');;';
    }
}