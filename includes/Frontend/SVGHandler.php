<?php
require_once __DIR__.'/../Constants.php';

class SVGHandler {
	private $frontendBreedingTree = null;
	
	//needed for setting the width of the svg tag
	private $highestXCoordinate = -1;
	const SVG_TAG_SAFETY_SPACE = 200;

	private $svgTag = '';

	public function __construct (
		FrontendPkmnObj $frontendBreedingTree,
		int $svgTagHeight,
	) {
		$this->frontendBreedingTree = $frontendBreedingTree;
		$this->svgTag = '<svg id="breedingParentsSVG" width="TEMP_WIDTH_PLACEHOLDER"'.
			' height="'.($svgTagHeight + self::SVG_TAG_SAFETY_SPACE).'">';
	}

	public function addOutput () {
		$this->createSVG();
		$this->setSVGWidth();

		//style tag is set in order to hide the overflown pkmn icons
		//		before styles.css is fully loaded
		$svgContainer = '<div id="breedingParentsSVGContainer"'.
			' style="overflow: hidden;">'.$this->svgTag.'</div>';

		Constants::$out->addModules('breedingParentsModules');
		Constants::$out->addHTML($svgContainer);

		//adding button that resets the svg to the starting position
		Constants::$out->addHTML('<input type="button" id="breedingParentsSVGResetButton"'.
			' value="Position zurücksetzen" />');
	}

	private function setSVGWidth () {
		$width = $this->highestXCoordinate + self::SVG_TAG_SAFETY_SPACE;
		$this->svgTag = str_replace('TEMP_WIDTH_PLACEHOLDER', $width, $this->svgTag);
	}

	private function createSVG () {
		$this->createSVGElements($this->frontendBreedingTree);

		$this->svgTag .= '</svg>';
	}

	private function addLine (int $startX, int $startY, int $endX, int $endY) {
		$svgLine = '<line x1="'.$startX.'" y1="'.$startY.'"'.
			' x2="'.$endX.'" y2="'.$endY.'" />';
		$this->svgTag .= $svgLine;
	}

	private function createSVGElements (FrontendPkmnObj $node) {
		$this->addPkmnIcon($node);

		$generalMargin = 15;

		$successorList = $node->getSuccessors();
		$successorsLength = count($successorList);
		if ($successorsLength === 0) {
			return;
		}

		$highest = $successorList[0]->getMiddleY();

		$lastSuccessor = $successorList[count($successorList) - 1];
		$lowest = $lastSuccessor->getMiddleY();

		$column = $node->getMiddleX() + Constants::PKMN_MARGIN_HORI / 1.5;

		if ($successorsLength === 1) {
			$this->adjustMiddleLineCoords($node, $highest, $lowest);
		}

		$this->addLine($column, $highest, $column, $lowest);

		$this->addLine($node->getX() + $node->getWidth() + $generalMargin, $node->getMiddleY(), $column, $node->getMiddleY());

		foreach ($node->getSuccessors() as $successor) {
			$coords = [
				'startX' => $column,
				'startY' => $successor->getMiddleY(),
				'endX' => $successor->getX() - $generalMargin,
				'endY' => $successor->getMiddleY()
			];

			if ($coords['endX'] > $this->highestXCoordinate) {
				//highest x coordinate is needed for setting the width of the svg tag
				$this->highestXCoordinate = $coords['endX'];
			}

			$this->addLine(
				$coords['startX'],
				$coords['startY'],
				$coords['endX'],
				$coords['endY']
			);

			$this->createSVGElements($successor);
		}
	}

	public function adjustMiddleLineCoords ($node, &$highest, &$lowest) {
		$successor = $node->getSuccessors()[0];
		if ($node->getMiddleY() < $successor->getMiddleY()) {
			$lowest = $node->getMiddleY();
			return;
		}
		if ($node->getMiddleY() > $successor->getMiddleY()) {
			$highest = $node->getMiddleY();
			return;
		}
	}

	private function addPkmnIcon (FrontendPkmnObj $pkmn) {
		if ($pkmn->getFileError() === '') {
			//todo maybe make the click/touch area for the link bigger
			$link = '<a href="https://www.pokewiki.de/'.$pkmn->getName().'#Attacken">';

			$icon = '<image x="'.$pkmn->getIconX().'" y="'.$pkmn->getY().'"'. 
				' width="'.$pkmn->getIconWidth().'"'.
				' height="'.$pkmn->getIconHeight().'"'.
				' xlink:href="'.$pkmn->getIconUrl().'" />';

			$this->svgTag .= $link.$icon.'</a>';

			if ($pkmn->getLearnsByEvent()) {
				$this->addEventMarker($pkmn);
			}
		} else {
			//executed when the icon file couldn't get loaded
			$x = $pkmn->getX();
			$y = $pkmn->getY(); 

			$text = '<text x="'.$x.'" y="'.$y.'">'.	
				'<tspan x="'.$x.'" y="'.$y.'">Oh, das hätte nicht passieren sollen :\'(</tspan>'.
				'<tspan x="'.$x.'" y="'.($y + 20).'">Melde das bitte auf unserem</tspan>'.
				'<tspan x="'.$x.'" y="'.($y + 40).'">Discordserver oder in der'.
				' <a href="https://www.pokewiki.de/Pok%C3%A9Wiki:Auskunft">Auskunft</a> ^^</tspan>'.
				'<tspan x="'.$x.'" y="'.($y + 60).'">Fehler beim Laden'.
				' von "'.$pkmn->getName().'"</tspan></text>';

			$this->svgTag .= $text;
		}
	}

	private function addEventMarker (FrontendPkmnObj $pkmn) {
		$x = $pkmn->getEventTextX();

		//for some reasons the y coordinate refers to the middle - not the top -
		//of the text
		$y = $pkmn->getY() + $pkmn->getIconHeight() + 10;
		$text = '<text x="'.$x.'" y="'.$y.'">Event</text>';
		$this->svgTag .= $text;
	}
}