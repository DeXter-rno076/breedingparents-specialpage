<?php
require_once __DIR__.'/../Logger.php';
require_once 'VisualElement.php';
require_once 'VisualPreparationNode.php';

abstract class VisualCircle extends VisualElement {
    protected $centerX;
    protected $centerY;
    protected $radius;
    protected $color;

    protected $learnabilityCode;

    public function __construct (int $centerX, int $centerY, int $radius, string $color, VisualPreparationNode $visualNode) {
        parent::__construct('circle', $visualNode->getGroupId());
        $this->centerX = $centerX;
        $this->centerY = $centerY;
        $this->radius = $radius;
        $this->color = $color;

        $this->learnabilityCode = $visualNode->getLearnabilityCode();

        Logger::statusLog('created '.$this);
    }
}