<?php
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';

require_once 'SVGElement.php';
require_once 'FrontendPkmn.php';

class SVGImg extends SVGElement {
	private $x;
	private $y;
	private $width;
	private $height;
	private $href;

	public function __construct (FrontendPkmn $frontendPkmn, int $groupId) {
		parent::__construct('image', $groupId);

		$this->x = $frontendPkmn->getX();
		$this->y = $frontendPkmn->getY();
		$this->width = $frontendPkmn->getIconWidth();
		$this->height = $frontendPkmn->getIconHeight();

		$this->href = $frontendPkmn->getIconUrl();

		Logger::statusLog('created '.$this);
	}

	public function toHTML (int $xOffset, int $yOffset): HTMLElement {
		return new HTMLElement('image', [
			'x' => $this->x + $xOffset,
			'y' => $this->y + $yOffset,
			'width' => $this->width,
			'height' => $this->height,
			'xlink:href' => $this->href,
			'groupid' => $this->groupId
		]);
	}

	public function getLogInfo (): string {
		return '\'\'\'SVGImg\'\'\':('.$this->x.';'.$this->y.');href='.$this->href.';;';
	}
}