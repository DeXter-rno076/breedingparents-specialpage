<?php
require_once 'BreedingSubtree.php';
require_once 'BreedingTreeNode.php';
require_once 'PkmnTreeRoot.php';
require_once __DIR__.'/../Logger.php';

class BreedingRootSubtree extends BreedingSubtree {
    protected $root;

    public function __construct(
        PkmnTreeRoot $root,
        array $successors
    )
    {
        $this->root = $root;
        $this->successors = $successors;
        $this->hash = 'root - hash is unnecessary';
    }

    public function getRoot (): PkmnTreeRoot {
        return $this->root;
    }

    public function addRoot (BreedingTreeNode $node) {
        Logger::wlog('called addRoot on BreedingRootSubtree');
    }

    public function getRoots (): array {
        return [$this->root];
    }
}