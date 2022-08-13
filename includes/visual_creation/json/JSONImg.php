<?php
require_once __DIR__.'/../VisualImg.php';
require_once 'JSONElement.php';

class JSONImg extends VisualImg implements JSONElement {
    public function compile (int $xOffset, int $yOffset): array {
        return [
            'tag' => 'image',
            'x' => $this->x + $xOffset,
            'y' => $this->y + $yOffset,
            'width' => $this->width,
            'height' => $this->height,
            'xlink:href' => $this->href,
            'groupid' => $this->groupId,
            'learnability' => $this->learnabilityCode
        ];
    }

    public function getLogInfo (): string {
        return '\'\'\'JSONImg\'\'\':('.$this->x.';'.$this->y.');href='.$this->href.';;';
    }
}