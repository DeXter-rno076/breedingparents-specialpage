<?php
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';
require_once __DIR__.'/../Constants.php';

require_once 'VisualNode.php';
require_once 'SVGElement.php';
require_once 'SVGImg.php';

class SVGLink extends SVGElement {
	private $href;
	private $innerEl;

	public function __construct(VisualNode $visualNode, SVGImg $innerEl, int $groupId) {
		parent::__construct('a', $groupId);
		$this->href = $visualNode->getArticleLink();
		$this->innerEl = $innerEl;

		Logger::statusLog('created '.$this);
	}

	public function toHTML (int $xOffset, int $yOffset): HTMLElement {
		return new HTMLElement('a', [
			'href' => $this->href,
			'groupid' => $this->groupId
		], [
			$this->innerEl->toHTML($xOffset, $yOffset)
		]);
	}

	public function getLogInfo (): string {
		return 'SVGLink:\'\'\''.$this->href.'\'\'\';;';
	}
}