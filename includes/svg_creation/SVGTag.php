<?php
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';
require_once __DIR__.'/../Constants.php';

require_once 'SVGElement.php';
require_once 'SVGPkmn.php';
require_once 'FrontendPkmn.php';

class SVGTag extends SVGElement {
	private $id = 'breedingChainsSVG';
	private $width;
	private $height;
	private $svgRoot;

	public function __construct (FrontendPkmn $pkmnRoot) {
		parent::__construct('svg');
		Logger::statusLog('creating SVGRoot instance');

		$this->width = $this->calculateWidth($pkmnRoot->getDepth());
		$this->height = $this->calculateHeight($pkmnRoot->getTreeSectionHeight());

		$this->svgRoot = new SVGPkmn($pkmnRoot);
	}

	private function calculateWidth (int $treeDepth): int {
		return ($treeDepth - 1) * Constants::PKMN_MARGIN_HORIZONTAL + Constants::SVG_OFFSET
		+ Constants::SVG_SAFETY_MARGIN;
	}

	private function calculateHeight (int $treeSectionHeight): int {
		return $treeSectionHeight + Constants::SVG_OFFSET + Constants::SVG_SAFETY_MARGIN;
	}

	public function toHTML (
		int $xOffset = Constants::SVG_OFFSET,
		int $yOffset = Constants::SVG_OFFSET
	): HTMLElement {
		$svgTag = new HTMLElement('svg', [
			'id' => $this->id,
			'xmlns' => 'http://www.w3.org/2000/svg',
			'viewbox' => '0 0 '.$this->width.' '.$this->height,
		]);
		
		$topLevelSVGTags = $this->svgRoot->toHTML($xOffset, $yOffset);

		foreach ($topLevelSVGTags as $tag) {
			$svgTag->addInnerElement($tag);
		}

		return $svgTag;
	}

	public function getLogInfo (): string {
		return '\'\'\'SVGTag\'\'\':;;';
	}
}