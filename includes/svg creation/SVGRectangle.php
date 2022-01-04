<?php
require_once 'SVGElement.php';
require_once __DIR__.'/../Logger.php';

class SVGRectangle extends SVGElement {
    private int $x;
    private int $y;
    private int $width;
    private int $height;
    //rounded corners
    private int $rx;
    private int $ry;

    public function __construct (int $x, int $y, int $width, int $height) {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;

        $cornerRounding = $width / Constants::SVG_RECT_PADDING;
        $this->rx = $cornerRounding;
        $this->ry = $cornerRounding;

        Logger::statusLog('created '.$this);
    }

    public function toHTMLString (int $offset): string {
        $x = $this->x + $offset;
        $y = $this->y + $offset;
        return '<rect x="'.$x.'" y="'.$y
            .'" width="'.$this->width.'" height="'.$this->height
            .'" rx="'.$this->rx.'" ry="'.$this->ry.'" />';
    }

    public function getLogInfo (): string {
        return '\'\'\'SVGRectangle\'\'\':('.$this->x.';'.$this->y.');'.$this->width.';'.$this->height.';;';
    }
}