<?php
require_once 'BreedingTreeNode.php';
require_once 'BreedingSubtree.php';

class MiscTreeNode extends BreedingTreeNode {
	private $iconName;
	
	public function __construct (string $nodeTitle, string $iconName) {
		parent::__construct($nodeTitle);
		$this->iconName = $iconName;
	}
	
	public function createBreedingSubTree (array $eggGroupBlacklist): ?BreedingSubtree {
		return new BreedingSubtree($this, [], '', []);
	}

	public function buildIconName (): string {
		return $this->iconName;
	}

	public function getLogInfo (): string {
		return 'MiscTreeNode:\'\'\''.$this->name.'\'\'\';;';
	}
}