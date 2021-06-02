<?php
require_once __DIR__.'/../Constants.php';

class SVGHandler {
	private $frontendBreedingTree = null;
	
	//needed for setting the width of the svg tag
	private $highestXCoordinate = -1;
	const SVG_TAG_SAFETY_SPACE = 100;

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

		foreach ($node->getSuccessors() as $successor) {
			$coords = $this->getCoordinates($node, $successor);

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

	private function getCoordinates (
		FrontendPkmnObj $node,
		FrontendPkmnObj $successor
	) : Array {
		//slope (dt.: Steigung) doesn't need centered coordinates
		$m = ($successor->getY() - $node->getY()) / ($successor->getX() - $node->getX());

		//how long the distance between starting/ending point 
		//	of the connection line to the corresponding icon shall be
		$generalMargin = 5;

		$nodeMargin = $this->getMargin(
			$node->getHeight(),
			$node->getWidth(),
			$generalMargin
		);
		$nodeDs = $this->getDeltas($nodeMargin, $m);

		$successorMargin = $this->getMargin(
			$successor->getHeight(),
			$successor->getWidth(),
			$generalMargin
		);
		$successorDs = $this->getDeltas($successorMargin, $m);

		return $this->calcCoordinates(
			$node, $successor, $nodeDs, $successorDs
		);
	}

	private function calcCoordinates (
		FrontendPkmnObj $node,
		FrontendPkmnObj $successor,
		Array $nodeDs,
		Array $successorDs	
	) : Array {
		$startX = $node->getX() + $nodeDs['dx']
			+ $node->getWidth() / 2;

		$startY = $node->getY() + $nodeDs['dy']
			+ $node->getHeight() / 2;

		$endX = $successor->getX() - $successorDs['dx']
			+ $successor->getWidth() / 2;

		$endY = $successor->getY() - $successorDs['dy']
			+ $successor->getHeight() / 2;

		return [
			'startX' => $startX,
			'startY' => $startY,
			'endX' => $endX,
			'endY' => $endY
		];
	}

	private function getMargin (int $height, int $width, int $margin) : int {
		$longerPart = max($height, $width);
		return ($longerPart / 2) + $margin;
	}

	private function getDeltas (int $margin, float $m) : Array {
		//tries x coordinates until it reaches a suiting margin to the icon
		$dx = 0;
		$dy = 0;
		$curMargin = 0;
		for (; $curMargin < $margin; $dx++) {
			//basic y = mx + t structure (but t = 0)
			$dy = $m * $dx;
			$curMargin = sqrt($dx ** 2 + $dy ** 2);
		}

		return [
			'dx' => $dx,
			'dy' => $dy
		];
	}

	private function addPkmnIcon (FrontendPkmnObj $pkmn) {
		if ($pkmn->getFileError() === '') {
			//todo maybe make the click/touch area for the link bigger
			$link = '<a href="https://www.pokewiki.de/'.$pkmn->getPkmnName().'#Attacken">';

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
				' von "'.$pkmn->getPkmnName().'"</tspan></text>';

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