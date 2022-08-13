<?php
require_once __DIR__.'/../VisualLink.php';
require_once 'JSONElement.php';

class JSONLink extends VisualLink implements JSONElement {
    public function compile (int $xOffset, int $yOffset): array {
        return [
            'tag' => 'a',
            'href' => $this->href,
            'groupid' => $this->groupId,
            'pkmn-name' => $this->pkmnName,
            'innerContent' => [
                $this->innerEl->compile($xOffset, $yOffset)
            ]
        ];
    }

    public function getLogInfo (): string {
        return 'JSONLink:\'\'\''.$this->href.'\'\'\';;';
    }
}