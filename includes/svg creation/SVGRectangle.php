<?php
require_once 'SVGElement.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';

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

        $cornerRounding = $width / Constants::SVG_RECTANGLE_PADDING;
        $this->rx = $cornerRounding;
        $this->ry = $cornerRounding;

        Logger::statusLog('created '.$this);
    }

    public function toHTML(int $xOffset, int $yOffset): HTMLElement {
        return new HTMLElement('rect', [
            'x' => $this->x + $xOffset,
            'y' => $this->y + $yOffset,
            'height' => $this->height,
            'width' => $this->width,
            'rx' => $this->rx,
            'ry' => $this->ry
        ]);
    }

    public function getLogInfo (): string {
        return '\'\'\'SVGRectangle\'\'\':('.$this->x.';'.$this->y.');'.$this->width.';'.$this->height.';;';
    }
}