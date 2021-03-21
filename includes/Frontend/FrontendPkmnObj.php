<?php
class FrontendPkmnObj {
	private $pkmnId;
	private $x;
	private $y;
	private $successors;

	public function __construct ($pkmnId, $x, $y) {
		$this->pkmnId = $pkmnId;
		$this->x = $x;
		$this->y = $y;
		$this->successors = [];
	}

	public function addSuccessor ($successor) {
		array_push($this->successors, $successor);
	}

	public function getSuccessors () {
		return $this->successors;
	}

	public function getPkmnId () {
		return $this->pkmnId;
	}

	public function getX () {
		return $this->x;
	}

	public function getY () {
		return $this->y;
	}
}
?>