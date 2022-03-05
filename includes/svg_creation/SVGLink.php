<?php
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';
require_once __DIR__.'/../Constants.php';

require_once 'SVGElement.php';
require_once 'SVGImg.php';

class SVGLink extends SVGElement {
	private string $href;
	private SVGImg $innerEl;

	public function __construct(string $pkmnName, SVGImg $innerEl) {
		parent::__construct('a');
		$this->href = $pkmnName.'/Attacken#'.Constants::$targetGenNumber.'. Generation';
		$this->innerEl = $innerEl;

		Logger::statusLog('created '.$this);
	}

	public function toHTML (int $xOffset, int $yOffset): HTMLElement {
		return new HTMLElement('a', [
			'href' => $this->href
		], [
			$this->innerEl->toHTML($xOffset, $yOffset)
		]);
	}

	public function getLogInfo (): string {
		return 'SVGLink:\'\'\''.$this->href.'\'\'\';;';
	}
}