<?php
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';

require_once 'SVGElement.php';

class SVGCircle extends SVGElement {
    private $centerX;
    private $centerY;
    private $radius;
    private $color;

    private $learnabilityCode;

    public function __construct (int $centerX, int $centerY, int $radius, string $color, VisualNode $visualNode) {
        parent::__construct('circle', $visualNode->getGroupId());
        $this->centerX = $centerX;
        $this->centerY = $centerY;
        $this->radius = $radius;
        $this->color = $color;

        $this->learnabilityCode = $visualNode->getLearnabilityCode();

        Logger::statusLog('created '.$this);
    }

    public function toHTML (int $xOffset, int $yOffset): HTMLElement {
        $circle = new HTMLElement('circle', [
            'cx' => $this->centerX + $xOffset,
            'cy' => $this->centerY + $yOffset,
            'r' => $this->radius,
            'color' => $this->color,
            'groupid' => $this->groupId,
            'learnability' => $this->learnabilityCode
        ]);

        return $circle;
    }

    /**
     * @return string SVGCircle:(<cx>;<cy>);<r>;;
     */
    public function getLogInfo (): string {
        return '\'\'\'SVGCircle\'\'\':('.$this->centerX.';'.$this->centerY.');'.$this->radius.';;';
    }
}