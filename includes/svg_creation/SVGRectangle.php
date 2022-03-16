<?php
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';
require_once __DIR__.'/../Constants.php';

require_once 'SVGElement.php';

class SVGRectangle extends SVGElement {
	private $x;
	private $y;
	private $width;
	private $height;
	//rounded corners
	private $rx;
	private $ry;

	public function __construct (int $x, int $y, int $width, int $height, int $groupId) {
		parent::__construct('rect', $groupId);

		$this->x = $x;
		$this->y = $y;
		$this->width = $width;
		$this->height = $height;

		$cornerRounding = $width / Constants::SVG_RECTANGLE_PADDING;
		$this->rx = $cornerRounding;
		$this->ry = $cornerRounding;

		Logger::statusLog('created '.$this);
	}

	public function toHTML(int $xOffset, int $yOffset): HTMLElement {
		return new HTMLElement('rect', [
			'x' => $this->x + $xOffset,
			'y' => $this->y + $yOffset,
			'height' => $this->height,
			'width' => $this->width,
			'rx' => $this->rx,
			'ry' => $this->ry,
			'groupid' => $this->groupId
		]);
	}

	public function getLogInfo (): string {
		return '\'\'\'SVGRectangle\'\'\':('.$this->x.';'.$this->y.');'.$this->width.';'.$this->height.';;';
	}
}