<?php
require_once __DIR__.'/../Logger.php';

require_once 'VisualElement.php';
require_once 'VisualPreparationNode.php';

abstract class VisualImg extends VisualElement {
    protected $x;
    protected $y;
    protected $width;
    protected $height;

    protected $href;

    protected $learnabilityCode;

    public function __construct (VisualPreparationNode $visualNode) {
        parent::__construct('image', $visualNode->getGroupId());

        $this->x = $visualNode->getIconX();
        $this->y = $visualNode->getIconY();
        $this->width = $visualNode->getIconWidth();
        $this->height = $visualNode->getIconHeight();

        $this->href = $visualNode->getIconUrl();

        $this->learnabilityCode = $visualNode->getLearnabilityCode();
        Logger::statusLog('learnability code for '.$visualNode->getName().': '.$this->learnabilityCode);

        Logger::statusLog('created '.$this);
    }
}