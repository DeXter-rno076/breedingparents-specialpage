<?php
require_once 'BreedingTreeNode.php';

class BreedingSubtree {
    protected $roots;
    protected $successors;
    protected $hash;

    public function __construct (
        BreedingTreeNode $initialRoot,
        array $successors,
        string $targetEggGroup,
        array $blacklistedEggGroups
    ) {
        $this->roots = [ $initialRoot ];
        $this->successors = $successors;
        $this->hash = BreedingSubtree::buildHash($targetEggGroup, $blacklistedEggGroups);
    }

    public function addRoot (BreedingTreeNode $root) {
        $this->roots[] = $root;
    }

    public function getRoots (): array {
        return $this->roots;
    }

    public function getSuccessors (): array {
        return $this->successors;
    }

    public function addSuccessor (BreedingSubtree $node) {
        $this->successors[] = $node;
    }

    public function addSuccessors (array $nodes) {
        $this->successors = array_merge($this->successors, $nodes);
    }

    public function hasSuccessors (array $successors = null): bool {
        if (is_null($successors)) {
            $successors = $this->successors;
        }
        return count($successors) > 0;
    }

    public function getHash (): string {
        return $this->hash;
    }

    public static function buildHash (string $targetEggGroup, array $blacklistedEggGroups) {
        //without sorting the egg groups the subtree structures don't stretch across multiple subtrees
        // -> far easier to handle (no sorting and dynamic line calculation needed)
        //sort($blacklistedEggGroups);
        return $targetEggGroup.'-'.join('', $blacklistedEggGroups);
    }
}