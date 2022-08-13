<?php
require_once __DIR__.'/../VisualLine.php';
require_once 'JSONElement.php';

class JSONLine extends VisualLine implements JSONElement {
    public function compile (int $xOffset, int $yOffset): array {
        return [
            'tag' => 'line',
            'x1' => $this->x1 + $xOffset,
            'y1' => $this->y1 + $yOffset,
            'x2' => $this->x2 + $xOffset,
            'y2' => $this->y2 + $yOffset,
            'groupid' => $this->groupId
        ];
    }

    public function getLogInfo (): string {
        return '\'\'\'JSONLine\'\'\':('.$this->x1.';'.$this->y1.')->('.$this->x2.';'.$this->y2.');;';
    }
}