<?php
require_once __DIR__.'/../VisualImg.php';
require_once 'SVGElement.php';
require_once __DIR__.'/../../HTMLElement.php';

class SVGImg extends VisualImg implements SVGElement {
    public function compile (int $xOffset, int $yOffset): HTMLElement {
        return new HTMLElement('image', [
            'x' => $this->x + $xOffset,
            'y' => $this->y + $yOffset,
            'width' => $this->width,
            'height' => $this->height,
            'xlink:href' => $this->href,
            'groupid' => $this->groupId,
            'learnability' => $this->learnabilityCode
        ]);
    }

    public function getLogInfo (): string {
        return '\'\'\'SVGImg\'\'\':('.$this->x.';'.$this->y.');href='.$this->href.';;';
    }
}