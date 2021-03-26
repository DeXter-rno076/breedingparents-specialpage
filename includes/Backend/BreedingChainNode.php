<?php
class BreedingChainNode {
	private $name;
	private $successors = [];

	//how much space the tree branch of this pkmn takes vertically
	private $treeSectionHeight;
	//offset of the tree branch on the y axis
	private $treeYOffset;

	private $learnsByEvent = false;

	public function __construct (String $name) {
		$this->name = $name;
	}

	public function getName () : String {
		return $this->name;
	}

	public function addSuccessor (BreedingChainNode $successor) {
		array_push($this->successors, $successor);
	}

	public function getSuccessors () : Array {
		return $this->successors;
	}

	public function setLearnsByEvent () {
		$this->learnsByEvent = true;
	}

	public function getLearnsByEvent () : bool {
		return $this->learnsByEvent;
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
}
?>