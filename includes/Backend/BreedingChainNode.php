<?php
class BreedingChainNode {
	private $name;
	private $successors = [];

	//how much space the tree branch of this pkmn takes vertically
	private $treeSectionHeight;
	//offset of the tree branch on the y axis
	private $treeYOffset;

	private $learnsByEvent = false;

	private $iconUrl = '';
	private $iconWidth = -1;
	private $iconHeight = -1;

	private $fileError = '';

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

	public function getHeight () : int {
		if ($this->getLearnsByEvent()) {
			return $this->getIconHeight() + 20;
		}
		return $this->getIconHeight();
	}

	//==========================================================
	//icon stuff
	//todo maybe outsource into a parent class

	public function setIconUrl (String $url) {
		$this->iconUrl = $url;
	}

	public function getIconUrl () : String {
		return $this->iconUrl;
	}

	public function setIconWidth (int $width) {
		$this->iconWidth = $width;
	}

	public function getIconWidth () : int {
		return $this->iconWidth;
	}

	public function setIconHeight (int $height) {
		$this->iconHeight = $height;
	}

	public function getIconHeight () : int {
		return $this->iconHeight;
	}

	public function setFileError (String $e) {
		$this->fileError = $e;
	}

	public function getFileError () : String {
		return $this->fileError;
	}
}