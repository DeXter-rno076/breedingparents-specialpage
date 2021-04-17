<?php
class FrontendPkmnObj {
	private $pkmnName = '';
	private $pkmnId = -1;
	private $x = -1;
	private $y = -1;
	private $successors = [];
	private $learnsByEvent = false;

	private $iconUrl = '';
	private $iconWidth = -1;
	private $iconHeight = -1;

	private $fileError = '';

	const EVENT_TEXT_HEIGHT = 20;
	const EVENT_TEXT_WIDTH = 41;
	const SAFETY_SPACE = 10;

	public function __construct (
		String $pkmnName,
		int $pkmnId,
		int $x,
		int $y,
		String $iconUrl,
		int $iconWidth,
		int $iconHeight
	) {
		$this->pkmnName = $pkmnName;
		$this->pkmnId = $pkmnId;
		$this->x = $x;
		$this->y = $y;
		$this->iconUrl = $iconUrl;
		$this->iconWidth = $iconWidth;
		$this->iconHeight = $iconHeight;
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
		return $this->x + self::SAFETY_SPACE;
	}

	public function getEventTextX () : int {
		return $this->getX() + $this->getPartXOffset(
			self::EVENT_TEXT_WIDTH,
			$this->getIconWidth()
		);
	}

	public function getIconX () : int {
		if (!$this->getLearnsByEvent()) {
			return $this->getX();
		} else {
			return $this->getX() + $this->getPartXOffset(
				$this->getIconWidth(),
				self::EVENT_TEXT_WIDTH
			);
		}
	}

	//depending on which is wider, the icon or the text have to be indented a bit
	private function getPartXOffset (int $targetW, int $otherW) : int {
		if ($otherW > $targetW) {
			return ($otherW - $targetW) / 2;
		} else {
			return 0;
		}
	}

	public function getY () : int {
		return $this->y + self::SAFETY_SPACE;
	}

	public function setLearnsByEvent () {
		$this->learnsByEvent = true;
	}

	public function getLearnsByEvent () : bool {
		return $this->learnsByEvent;
	}

	//==========================================================
	//icon stuff

	public function getIconUrl () : String {
		return $this->iconUrl;
	}

	public function getWidth () : int {
		if (!$this->getLearnsByEvent()) {
			return $this->iconWidth;
		} else {
			return max($this->iconWidth, self::EVENT_TEXT_WIDTH);
		}
	}

	public function getHeight () : int {
		if (!$this->getLearnsByEvent()) {
			return $this->iconHeight;
		} else {
			return $this->iconHeight + self::EVENT_TEXT_HEIGHT / 2;
		}
	}

	public function getIconWidth () : int {
		return $this->iconWidth;
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