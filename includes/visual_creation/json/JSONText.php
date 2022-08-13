<?php
require_once __DIR__.'/../VisualText.php';
require_once 'JSONElement.php';

class JSONText extends VisualText implements JSONElement {
    public function compile (int $xOffset, int $yOffset): array {
        return [
            'tag' => 'text',
            'x' => $this->x + $xOffset,
            'y' => $this->y + $yOffset,
            'groupid' => $this->groupId,
            'text' => htmlentities($this->text)
        ];
    }

    public function getLogInfo (): string {
        return 'JSONText:('.$this->x.';'.$this->y.');'.$this->text.';;';
    }
}