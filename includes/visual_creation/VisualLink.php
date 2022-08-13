<?php
require_once __DIR__.'/../Logger.php';
require_once 'VisualPreparationNode.php';
require_once 'VisualElement.php';
require_once 'VisualImg.php';

abstract class VisualLink extends VisualElement {
    protected $href;
    protected $innerEl;
    protected $pkmnName;

    public function __construct(VisualPreparationNode $visualNode, VisualImg $innerEl) {
        parent::__construct('a', $visualNode->getGroupId());
        $this->href = $visualNode->getArticleLink();
        $this->innerEl = $innerEl;
        $this->pkmnName = $visualNode->getCorrectlyWrittenName();

        Logger::statusLog('created '.$this);
    }
}