<?php
//todo <desc> for eventlearnset pkmn
//todo add links to pkmn icons
use MediaWiki\MediaWikiServices;

class SVGHandler {
	private $frontendBreedingTree = null;
	private $PKMN_ICON_HEIGHT = -1;//temporary
	
	//needed for setting the width of the svg tag
	private $highestXCoordinate = -1;

	private $svgTag = '';

	public function __construct ($frontendBreedingTree, $svgTagHeight, $PKMN_ICON_HEIGHT) {
		$this->frontendBreedingTree = $frontendBreedingTree;
		$this->svgTag = '<svg id="breedingParentsSVG" width="TEMP_WIDTH_PLACEHOLDER"'.
			' height="'.($svgTagHeight + 100).'">';
		$this->PKMN_ICON_HEIGHT = $PKMN_ICON_HEIGHT;
	}

	public function addOutput ($output) {
		$this->createSVG();
		$this->setSVGWidth();

		//style tag is set in order to hide the overflown pkmn icons
		//		before styles.css is fully loaded
		$svgContainer = '<div id="breedingParentsSVGContainer"'.
			' style="overflow: hidden;">'.$this->svgTag.'</div>';

		$output->addModules('breedingParentsModules');
		$output->addHTML($svgContainer);
		//adding button that resets the svg to the starting position
		$output->addHTML('<input type="button" id="breedingParentsSVGResetButton"'.
			' value="Position zurücksetzen" />');
	}

	private function setSVGWidth () {
		//todo safety margin to the right not final
		$width = $this->highestXCoordinate + 100;
		$this->svgTag = str_replace('TEMP_WIDTH_PLACEHOLDER', $width, $this->svgTag);
	}

	private function createSVG () {
		$this->createSVGElements($this->frontendBreedingTree);

		$this->svgTag .= '</svg>';
	}

	private function addLine ($startX, $startY, $endX, $endY) {
		//safety margin of 10px to upper and left border
		$svgLine = '<line x1="'.($startX + 10).'" y1="'.($startY + 10).'"'.
			' x2="'.($endX + 10).'" y2="'.($endY + 10).'" />';
		$this->svgTag .= $svgLine;
	}

	private function createSVGElements ($node) {
		$this->addPkmnIcon($node);

		//todo maybe outsource some stuff into a separate method
		foreach ($node->getSuccessors() as $successor) {
			//coordinates give position of the top left corner 
			//	-> Icon height / 2 has to be added/subtracted

			//slope (dt.: Steigung) doesn't need centered coordinates
			$m = ($successor->getY() - $node->getY()) / ($successor->getX() - $node->getX());

			$curCircMargin = 0;
			$dx = 0;
			$dy = 0;
			//todo margin is far too low (temporary value for testing)
			//how long the distance between starting/ending point 
			//	of the connection line to the corresponding icon shall be
			$MARGIN = 5;

			//tries x coordinates until it reaches a suiting margin to the icon
			for (; $curCircMargin < $MARGIN; $dx++) {
				//basic y = mx + t structure (but t = 0)
				$dy = $m * $dx;
				$curCircMargin = sqrt($dx ** 2 + $dy ** 2);
			}

			//todo centering of coordinates needs icon heights/widths
			$startX = $node->getX() + 13.5 + $dx;
			$startY = ($node->getY() - 10) + $dy;
			$endX = $successor->getX() + 13.5 - $dx;
			$endY = ($successor->getY() - 10) - $dy;

			if ($endX > $this->highestXCoordinate) {
				//highest x coordinate is needed for setting the width of the svg tag
				$this->highestXCoordinate = $endX;
			}

			$this->addLine($startX, $startY, $endX, $endY);

			$this->createSVGElements($successor);
		}
	}

	private function addPkmnIcon ($pkmn) {
		try {
			$iconUrl = $this->getIconUrl($pkmn->getPkmnId());
			//safety margin to upper and left border
			$icon = '<image x="'.($pkmn->getX() + 10).'" y="'.($pkmn->getY() + 10).'"'. 
				' width="'.$this->PKMN_ICON_HEIGHT.'"'.
				' height="'.$this->PKMN_ICON_HEIGHT.'" xlink:href="'.$iconUrl.'" />';
			$this->svgTag .= $icon;
		} catch (Exception $e) {
			$x = $pkmn->getX() + 10;
			$y = $pkmn->getY() + 10; 
			$text = '<text x="'.$x.'" y="'.$y.'">'.	
				'<tspan x="'.$x.'" y="'.$y.'">Oh, das hätte nicht passieren sollen.</tspan>'.
				'<tspan x="'.$x.'" y="'.($y + 20).'">Melde das bitte auf unserem</tspan>'.
				'<tspan x="'.$x.'" y="'.($y + 40).'">Discordserver oder in der'.
				' <a href="https://www.pokewiki.de/Pok%C3%A9Wiki:Auskunft">Auskunft</a></tspan>'.
				'<tspan x="'.$x.'" y="'.($y + 60).'">Fehler beim Laden'.
				' von "'.$pkmn->getPkmnId().'"</tspan></text>';
			$this->svgTag .= $text;
		}
	}

	private function getIconUrl ($pkmnId) {
		if ($pkmnId < 100) {
			$pkmnId = '0'.$pkmnId;
			if ($pkmnId < 10) {
				$pkmnId = '0'.$pkmnId;
			}
		}

		$fileName = 'Pokémon-Icon '.$pkmnId.'.png';
		$fileObj = MediaWikiServices::getInstance()->getRepoGroup()->findFile($fileName);
		//fileObj->getHeight(); fileObj->getWidth()

		if ($fileObj === false) {
			throw new Exception('pkmn icon not found');
		}

		return $fileObj->getUrl();
	}
}
?>