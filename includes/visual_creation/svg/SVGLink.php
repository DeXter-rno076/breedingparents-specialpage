<?php
require_once __DIR__.'/../VisualLine.php';
require_once 'SVGElement.php';
require_once __DIR__.'/../../HTMLElement.php';

class SVGLink extends VisualLink implements SVGElement {
    public function compile (int $xOffset, int $yOffset): HTMLElement {
        return new HTMLElement('a', [
            'href' => $this->href,
            'groupid' => $this->groupId,
            'pkmn-name' => $this->pkmnName
        ], [
            $this->innerEl->compile($xOffset, $yOffset)
        ]);
    }

    public function getLogInfo (): string {
        return 'SVGLink:\'\'\''.$this->href.'\'\'\';;';
    }
}