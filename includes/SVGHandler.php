<?php
//todo <desc> for eventlearnset pkmn
//todo add links to pkmn icons
class SVGHandler {
	private $objData = null;
	private $PKMN_ICON_HEIGHT = -1;//temporary
	
	private $highestXCoordinate = -1;

	private $svgTag = '';

	public function __construct ($objData, $svgHeight, $PKMN_ICON_HEIGHT) {
		$this->objData = $objData;
		$this->svgTag = '<svg id="breedingParentsSVG" width="TEMP_WIDTH_PLACEHOLDER" height="'.($svgHeight + 100).'">';
		$this->PKMN_ICON_HEIGHT = $PKMN_ICON_HEIGHT;
	}

	public function addOutput ($output) {
		$this->createSVG();
		$this->setSVGWidth();

		$svgContainer = '<div id="breedingParentsSVGContainer">'.$this->svgTag.'</div>';

		$this->addCSS($output);
		$output->addHTML($svgContainer);
	}

	private function addCSS ($output) {
		//todo add error handling (something like "fopen(...) or doStuff(...)")
		$filePath = 'extensions/BreedingParents/includes/styles.css';
		$cssFile = fopen($filePath, 'r');
		$css = fread($cssFile, filesize($filePath));
		fclose($cssFile);
		$output->addInlineStyle($css);
	}

	private function setSVGWidth () {
		//todo safety margin to the right not final
		$width = $this->highestXCoordinate + 100;
		$this->svgTag = str_replace('TEMP_WIDTH_PLACEHOLDER', $width, $this->svgTag);
	}

	private function createSVG () {
		$this->createSVGElements($this->objData);

		$this->svgTag .= '</svg>';
	}

	private function addLine ($startX, $startY, $endX, $endY) {
		//safety margin of 10px to upper and left border
		$svgLine = '<line x1="'.($startX + 10).'" y1="'.($startY + 10).'" x2="'.($endX + 10).'" y2="'.($endY + 10).'" />';
		$this->svgTag .= $svgLine;
	}

	private function createSVGElements ($node) {
		$this->addPkmnIcon($node);

		foreach ($node->getSuccessors() as $successor) {
			//coordinates give position of the top left corner -> Icon height / 2 has to be added/subtracted

			//slope (dt.: Steigung) doesn't need centered coordinates
			$m = ($successor->y - $node->y) / ($successor->x - $node->x);

			$length = 0;
			$dx = 0;
			$dy = 0;
			//todo margin is far too low (temporary value for testing)
			$MARGIN = 5;

			for (; $length < $MARGIN; $dx++) {
				$dy = $m * $dx;
				$length = sqrt($dx ** 2 + $dy ** 2);
			}

			//todo centering of coordinates needs icon heights/widths
			$startX = $node->x + 13.5 + $dx;
			$startY = ($node->y - 10) + $dy;
			$endX = $successor->x + 13.5 - $dx;
			$endY = ($successor->y - 10) - $dy;

			if ($endX > $this->highestXCoordinate) {
				//highest x coordinate is needed for setting the width of the svg tag
				$this->highestXCoordinate = $endX;
			}

			$this->addLine($startX, $startY, $endX, $endY);

			$this->createSVGElements($successor, $output);
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
		if (!is_null($fileLink)) {
			//safety margin to upper and left border
			$icon = '<image pkmnId="'.$pkmnId.'" x="'.($pkmn->x + 10).'" y="'.($pkmn->y + 10).'"'; 
			$icon = $icon.'width="'.$this->PKMN_ICON_HEIGHT.'" height="'.$this->PKMN_ICON_HEIGHT.'" xlink:href="'.$fileLink.'" />';
			$this->svgTag .= $icon;
		} else {
			//temporary
			$text = '<text x="'.($pkmn->x + 10).'" y="'.($pkmn->y + 10).'">'.$pkmnId.'</text>';
			$this->svgTag .= $text;
		}
	}
}
?>