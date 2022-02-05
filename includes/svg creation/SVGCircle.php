<?php
require_once 'SVGElement.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';

class SVGCircle extends SVGElement {
    private int $centerX;
    private int $centerY;
    private int $radius;

    public function __construct (int $centerX, int $centerY, int $radius) {
        $this->centerX = $centerX;
        $this->centerY = $centerY;
        $this->radius = $radius;

        Logger::statusLog('created '.$this);
    }

    public function toHTML (int $xOffset, int $yOffset): HTMLElement {
        return new HTMLElement('circle', [
            'cx' => $this->centerX + $xOffset,
            'cy' => $this->centerY + $yOffset,
            'r' => $this->radius
        ]);
    }

    /**
     * @return string SVGCircle:(<cx>;<cy>);<r>;;
     */
    public function getLogInfo (): string {
        return '\'\'\'SVGCircle\'\'\':('.$this->centerX.';'.$this->centerY.');'.$this->radius.';;';
    }
}