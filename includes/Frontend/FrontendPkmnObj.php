<?php
class FrontendPkmnObj {
	private $pkmnName = '';
	private $pkmnId = -1;
	private $x = -1;
	private $y = -1;
	private $successors = [];

	private $iconUrl = '';
	private $iconWidth = -1;
	private $iconHeight = -1;

	private $fileError = '';

	public function __construct (String $pkmnName, int $pkmnId, int $x, int $y) {
		$this->pkmnName = $pkmnName;
		$this->pkmnId = $pkmnId;
		$this->x = $x;
		$this->y = $y;
	}

	public function getPkmnName () : String {
		return $this->pkmnName;
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

	//==========================================================
	//icon stuff

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
?>