<?php
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';

require_once 'SVGElement.php';

class SVGText extends SVGElement {
    private $x;
    private $y;
    private $text;

    public function __construct (int $x, int $y, string $text, int $groupId) {
        parent::__construct('text', $groupId);

        $this->x = $x;
        $this->y = $y;
        $this->text = $text;

        Logger::statusLog('created '.$this);
    }

    public function toHTML (int $xOffset, int $yOffset): HTMLElement {
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