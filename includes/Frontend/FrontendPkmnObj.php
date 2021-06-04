<?php
require_once __DIR__.'/../PkmnObj.php';
class FrontendPkmnObj extends PkmnObj {
	private $x = -1;
	private $y = -1;

	const EVENT_TEXT_HEIGHT = 20;
	const EVENT_TEXT_WIDTH = 41;
	const SAFETY_SPACE = 10;

	public function __construct (
		String $name,
		int $x,
		int $y,
		String $iconUrl,
		int $iconWidth,
		int $iconHeight
	) {
		$this->name = $name;
		$this->x = $x;
		$this->y = $y;
		$this->iconUrl = $iconUrl;
		$this->iconWidth = $iconWidth;
		$this->iconHeight = $iconHeight;
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

	//==========================================================
	//icon stuff

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
}