<?php
class BreedingChainNode {
	//todo change access rights after implementing frontend
	public $name;
	public $successors = [];
	public $treeSectionHeight;
	public $treeYOffset;
	private $learnsByEvent = false;
	
	public function __construct ($name) {
		$this->name = $name;
	}

	public function addSuccessor ($successor) {
		array_push($this->successors, $successor);
	}

	public function getSuccessors () {
		return $this->successors;
	}

	public function setLearnsByEvent () {
		$this->learnsByEvent = true;
	}

	public function getLearnsByEvent () {
		return $this->learnsByEvent;
	}
}
?>