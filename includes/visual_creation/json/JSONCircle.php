<?php
require_once __DIR__.'/../VisualCircle.php';
require_once 'JSONElement.php';

class JSONCircle extends VisualCircle implements JSONElement {
    public function compile (int $xOffset, int $yOffset): array {
        return [
            'tag' => 'circle',
            'cx' => $this->centerX + $xOffset,
            'cy' => $this->centerY + $yOffset,
            'r' => $this->radius,
            'color' => $this->color,
            'groupid' => $this->groupId,
            'learnability' => $this->learnabilityCode
        ];
    }

    public function getLogInfo (): string {
        return '\'\'\'JSONCircle\'\'\':('.$this->centerX.';'.$this->centerY.');'.$this->radius.';;';
    }
}