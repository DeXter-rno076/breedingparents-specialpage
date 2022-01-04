<?php
require_once __DIR__.'/../Logger.php';
require_once 'SVGElement.php';
require_once 'SVGImg.php';

class SVGLink extends SVGElement {
    private string $href;
    private SVGImg $innerEl;

    public function __construct(string $pkmnName, SVGImg $innerEl) {
        parent::__construct('a');
        $this->href = $pkmnName.'/Attacken#'.Constants::$targetGen.'. Generation';
        $this->innerEl = $innerEl;

        Logger::statusLog('created '.$this);
    }

    public function toHTMLString(int $xOffset, int $yOffset): string {
        return '<a href="'.$this->href.'">'
        .$this->innerEl->toHTMLString($xOffset, $yOffset).'</a>';
    }

    public function getLogInfo (): string {
        return 'SVGLink:\'\'\''.$this->href.'\'\'\';;';
    }
}