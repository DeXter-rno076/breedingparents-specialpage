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

    public function toHTMLString (int $xOffset, int $yOffset): string {
        $x = $this->cx + $xOffset;
        $y = $this->cy + $yOffset;
        return '<circle cx="'.$x.'" cy="'.$y.'" r="'.$this->r.'" />';
    }

    public function getLogInfo (): string {
        return '\'\'\'SVGCircle\'\'\':('.$this->cx.';'.$this->cy.');'.$this->r.';;';
    }
}