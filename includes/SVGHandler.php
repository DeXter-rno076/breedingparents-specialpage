<?php
class SVGHandler {
	private $objData = null;
	private $maxDeepness = -1;
	private $svgTag = '';
	private $elementMargin = -1;
	private $PKMN_ICON_HEIGHT = -1;
	private $margin = 5;

	public function __construct ($objData, $maxDeepness, $svgHeight, $PKMN_ICON_HEIGHT) {
		//todo <desc> for eventlearnset pkmn
		$this->objData = $objData;
		$this->maxDeepness = $maxDeepness;
		$this->elementMargin = (100 / $this->maxDeepness) / 10;
		$this->svgTag = '<svg id="breedingParentsSVG" width="100%" height="'.($svgHeight + 100).'">\n';
		$this->PKMN_ICON_HEIGHT = $PKMN_ICON_HEIGHT;
	}

	public function createSVG ($output) {
		$this->createSVGElements($this->objData);

		$this->svgTag = $this->svgTag.'</svg>';

		$svgCSS = '#breedingParentsSVG line { stroke: black; stroke-width: 1;}';

		$output->addInlineStyle($svgCSS);
		$output->addHTML($this->svgTag);
	}

	private function addLine ($startX, $startY, $endX, $endY) {
		$svgElement = '<line x1="'.$startX.'%" y1="'.$startY.'" x2="'.$endX.'%" y2="'.$endY.'" />';
		$this->svgTag = $this->svgTag.$svgElement;
	}

	private function createSVGElements ($node) {
		//margin stuff and so on has to get adjusted
		$startX = $node->x + 5;

		$this->addPkmnIcon($node);

		foreach ($node->getSuccessors() as $successor) {
			//coordinates give position of the top left corner -> Icon height / 2 has to be added/subtracted
			//this can be omitted for x coordinates (can be compensated with margin)
			$endX = $successor->x - 1;
			//slope doesn't need centered coordinates
			$m = ($successor->y - $node->y) / ($successor->x - $node->x);
			//basic y = mx + t structure
			$startY = $m * ($startX - $node->x) + ($node->y + $this->PKMN_ICON_HEIGHT / 2);
			$endY = $m * ($endX - $node->x) + ($node->y + $this->PKMN_ICON_HEIGHT / 2);

			$this->addLine($startX, $startY, $endX, $endY);

			$this->createSVGElements($successor);
		}
	}

	private function addPkmnIcon ($pkmn) {
		$temp_fileLinkList = [
			610 => 'https://www.pokewiki.de/images/9/93/Pok%C3%A9mon-Icon_610.png',
			713 => 'https://www.pokewiki.de/images/c/c6/Pok%C3%A9mon-Icon_713.png',
			712 => 'https://www.pokewiki.de/images/5/5f/Pok%C3%A9mon-Icon_712.png',
			306 => 'https://www.pokewiki.de/images/7/77/Pok%C3%A9mon-Icon_306.png',
			305 => 'https://www.pokewiki.de/images/6/62/Pok%C3%A9mon-Icon_305.png',
			304 => 'https://www.pokewiki.de/images/4/48/Pok%C3%A9mon-Icon_304.png',
			611 => 'https://www.pokewiki.de/images/8/82/Pok%C3%A9mon-Icon_611.png',
			612 => 'https://www.pokewiki.de/images/a/a2/Pok%C3%A9mon-Icon_612.png'
		];

		$pkmnId = $pkmn->pkmnid;
		$fileLink = $temp_fileLinkList[$pkmnId];
		$icon = '<image x="'.$pkmn->x.'%" y="'.$pkmn->y.'"'; 
		$icon = $icon.'width="'.$this->PKMN_ICON_HEIGHT.'" height="'.$this->PKMN_ICON_HEIGHT.'" xlink:href="'.$fileLink.'" />';
		$this->svgTag = $this->svgTag.$icon;
	}
}
?>