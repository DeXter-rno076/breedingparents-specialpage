<?php
require_once 'SVGElement.php';
require_once __DIR__.'/../HTMLElement.php';
require_once __DIR__.'/../Logger.php';

class SVGText extends SVGElement {
    private int $x;
    private int $y;
    private string $text;

    public function __construct (int $x, int $y, string $text) {
        $this->x = $x;
        $this->y = $y;
        $this->text = $text;

        Logger::statusLog('created '.$this);
    }

    public function getLogInfo (): string {
        return 'SVGText:('.$this->x.';'.$this->y.');'.$this->text.';;';
    }

    public function toHTML (int $xOffset, int $yOffset): HTMLElement {
        $svgText = new HTMLElement('text',[
            'x' => $this->x + $xOffset,
            'y' => $this->y + $yOffset
        ]);

        $svgText->addInnerString($this->text);

        return $svgText;
    }
}