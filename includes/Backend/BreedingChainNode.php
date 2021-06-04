<?php
require_once __DIR__.'/../PkmnObj.php';
class BreedingChainNode extends PkmnObj {
	//how much space the tree branch of this pkmn takes vertically
	private $treeSectionHeight;
	//offset of the tree branch on the y axis
	private $treeYOffset;

	public function __construct (String $name) {
		$this->name = $name;
	}

	public function getTreeSectionHeight () : int {
		return $this->treeSectionHeight;
	}

	public function setTreeSectionHeight (int $height) {
		$this->treeSectionHeight = $height;
	}

	public function getTreeYOffset () : int {
		return $this->treeYOffset;
	}

	public function setTreeYOffset (int $offset) {
		$this->treeYOffset = $offset;
	}

	public function getHeight () : int {
		if ($this->getLearnsByEvent()) {
			return $this->getIconHeight() + 20;
		}
		return $this->getIconHeight();
	}	
}