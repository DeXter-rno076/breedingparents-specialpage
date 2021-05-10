<?php
require_once 'FrontendPreparator.php';
require_once 'SVGHandler.php';
require_once __DIR__.'/../Constants.php';

class FrontendHandler {
	private $breedingTree = null;
	
	public function __construct (BreedingChainNode $breedingTree) {
		$this->breedingTree = $breedingTree;
	}

	//adds svg tag to output (includes adding css and js files)
	public function addSVG () {
		//for performance measuring
		$timeStart = hrtime(true);

		//preparator creates a tree structure with objects
		//	that have only the necessary infos for SVGHandler
		$preparator = new FrontendPreparator();
		$svgObjectStructure = $preparator->prepareForFrontend($this->breedingTree);

		$svgTagHeight = $svgObjectStructure->svgTagHeight;
		$svgHandler = new SVGHandler(
			$svgObjectStructure,
			$svgTagHeight,
			$output
		);
		$svgHandler->addOutput();

		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		Constants::out('frontend needed '.$timeDiff.' seconds');
	}
}