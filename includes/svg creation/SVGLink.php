<?php
require_once __DIR__.'/../Logger.php';
require_once 'SVGElement.php';
require_once 'SVGImg.php';

class SVGLink extends SVGElement {
    private string $href;
    private SVGImg $innerEl;

    public function __construct(string $href, SVGImg $innerEl) {
        parent::__construct('a');
        $this->href = $href;
        $this->innerEl = $innerEl;

        Logger::statusLog('created '.$this);
    }

    public function toHTMLString(int $offset): string {
        return '<a href="'.$this->href.'#Attacken">'
        .$this->innerEl->toHTMLString($offset).'</a>';
    }

    public function getLogInfo (): string {
        return 'SVGLink:'.$this->href.';;';
    }
}