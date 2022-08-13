<?php
require_once __DIR__.'/../Logger.php';

require_once 'VisualElement.php';
require_once 'VisualSubtree.php';
require_once 'VisualPreparationSubtree.php';

abstract class VisualRoot extends VisualElement {
    protected $width;
    protected $height;
    protected $tree;

    public function __construct (VisualPreparationSubtree $pkmnRoot, int $groupId) {
        parent::__construct('root', $groupId);
        Logger::statusLog('creating visual root instance');

        $this->width = $this->calculateWidth($pkmnRoot->getDepth());
        $this->height = $this->calculateHeight($pkmnRoot->getSubtreeHeight());

        $this->tree = $this->instantiateVisualSubtree($pkmnRoot);
    }

    protected abstract function instantiateVisualSubtree (VisualPreparationSubtree $pkmnRoot): VisualSubtree;

    protected abstract function calculateWidth (int $treeDepth): int;

    protected abstract function calculateHeight (int $treeSectionHeight): int;
}