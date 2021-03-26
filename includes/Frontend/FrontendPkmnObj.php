<?php
class FrontendPkmnObj {
	private $pkmnId;
	private $x;
	private $y;
	private $successors;

	public function __construct (int $pkmnId, int $x, int $y) {
		$this->pkmnId = $pkmnId;
		$this->x = $x;
		$this->y = $y;
		$this->successors = [];
	}

	public function addSuccessor (FrontendPkmnObj $successor) {
		array_push($this->successors, $successor);
	}

	public function getSuccessors () : Array {
		return $this->successors;
	}

	public function getPkmnId () : int {
		return $this->pkmnId;
	}

	public function getX () : int {
		return $this->x;
	}

	public function getY () : int {
		return $this->y;
	}
}
?>