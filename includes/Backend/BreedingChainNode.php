<?php
class BreedingChainNode {
	private $name;
	private $successors = [];

	//how much space the tree branch of this pkmn takes vertically
	private $treeSectionHeight;
	//offset of the tree branch on the y axis
	private $treeYOffset;

	private $learnsByEvent = false;
	
	public function __construct ($name) {
		$this->name = $name;
	}

	public function getName () {
		return $this->name;
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

	public function getTreeSectionHeight () {
		return $this->treeSectionHeight;
	}

	public function setTreeSectionHeight ($height) {
		$paramType = gettype($height);
		if ($paramType !== 'integer' && $paramType !== 'double') {
			$excMsg = 'setTreeSectionHeight: invalid parameter type; integer or double expected, but got type '.$paramType;
			throw new UnexpectedValueException($excMsg);
		}
		$this->treeSectionHeight = $height;
	}

	public function getTreeYOffset () {
		return $this->treeYOffset;
	}

	public function setTreeYOffset ($offset) {
		$paramType = gettype($offset);
		if ($paramType !== 'integer' && $paramType !== 'double') {
			$excMsg = 'setTreeYOffset: invalid parameter type; integer or double expected, but got type '.$paramType;
			throw new UnexpectedValueException($excMsg);
		}
		$this->treeYOffset = $offset;
	}
}
?>