<?php
require_once __DIR__.'/../VisualText.php';
require_once 'SVGElement.php';
require_once __DIR__.'/../../HTMLElement.php';

class SVGText extends VisualText implements SVGElement {
    public function compile (int $xOffset, int $yOffset): HTMLElement {
        $svgText = new HTMLElement('text',[
            'x' => $this->x + $xOffset,
            'y' => $this->y + $yOffset,
            'groupid' => $this->groupId
        ]);

        $svgText->addInnerString($this->text);

        return $svgText;
    }

    public function getLogInfo (): string {
        return 'SVGText:('.$this->x.';'.$this->y.');'.$this->text.';;';
    }
}