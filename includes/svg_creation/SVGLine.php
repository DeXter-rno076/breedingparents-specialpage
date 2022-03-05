<?php
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';

require_once 'SVGElement.php';

class SVGLine extends SVGElement {
	private int $x1;
	private int $y1;
	private int $x2;
	private int $y2;

	public function __construct (int $x1, int $y1, int $x2, int $y2) {
		parent::__construct('line');
		$this->x1 = $x1;
		$this->y1 = $y1;
		$this->x2 = $x2;
		$this->y2 = $y2;

		Logger::statusLog('created '.$this);
	}

	public function toHTML (int $xOffset, int $yOffset): HTMLElement {
		return new HTMLElement('line', [
			'x1' => $this->x1 + $xOffset,
			'y1' => $this->y1 + $yOffset,
			'x2' => $this->x2 + $xOffset,
			'y2' => $this->y2 + $yOffset
		]);
	}

	public function getLogInfo (): string {
		return '\'\'\'SVGLine\'\'\':('.$this->x1.';'.$this->y1.')->('.$this->x2.';'.$this->y2.');;';
	}
}