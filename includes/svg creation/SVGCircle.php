<?php
require_once 'SVGElement.php';
require_once __DIR__.'/../Logger.php';

class SVGCircle extends SVGElement {
    private int $cx;
    private int $cy;
    private int $r;

    public function __construct (int $cx, int $cy, int $r) {
        $this->cx = $cx;
        $this->cy = $cy;
        $this->r = $r;

        Logger::statusLog('created '.$this);
    }

    public function toHTML (int $xOffset, int $yOffset): HTMLElement {
        return new HTMLElement('circle', [
            'x' => $this->cx + $xOffset,
            'y' => $this->cy + $yOffset,
            'r' => $this->r
        ]);
    }

    public function getLogInfo (): string {
        return '\'\'\'SVGCircle\'\'\':('.$this->cx.';'.$this->cy.');'.$this->r.';;';
    }
}