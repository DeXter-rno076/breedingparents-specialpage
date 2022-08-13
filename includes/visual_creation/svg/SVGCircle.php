<?php
require_once __DIR__.'/../VisualCircle.php';
require_once 'SVGElement.php';
require_once __DIR__.'/../../HTMLElement.php';

class SVGCircle extends VisualCircle implements SVGElement {
    public function compile (int $xOffset, int $yOffset): HTMLElement {
        $circle = new HTMLElement('circle', [
            'cx' => $this->centerX + $xOffset,
            'cy' => $this->centerY + $yOffset,
            'r' => $this->radius,
            'color' => $this->color,
            'groupid' => $this->groupId,
            'learnability' => $this->learnabilityCode
        ]);

        return $circle;
    }

    public function getLogInfo (): string {
        return '\'\'\'SVGCircle\'\'\':('.$this->centerX.';'.$this->centerY.');'.$this->radius.';;';
    }
}