<?php
require_once 'FrontendPreparator.php';
require_once 'SVGHandler.php';

class FrontendHandler {
	private $breedingTree = null;
	private $pkmnData = null;
	
	public function __construct (BreedingChainNode $breedingTree, StdClass $pkmnData) {
		$this->breedingTree = $breedingTree;
		$this->pkmnData = $pkmnData;
	}

	//adds svg tag to output (includes adding css and js files)
	public function addSVG (OutputPage $output) {
		//for performance measuring
		$timeStart = hrtime(true);

		//preparator creates a tree structure with objects
		//	that have only the necessary infos for SVGHandler
		$preparator = new FrontendPreparator($this->pkmnData);
		$svgObjectStructure = $preparator->prepareForFrontend($this->breedingTree);

		$svgTagHeight = $svgObjectStructure->svgTagHeight;
		$svgHandler = new SVGHandler(
			$svgObjectStructure,
			$svgTagHeight,
			$output
		);
		$svgHandler->addOutput($output);

		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		$output->addHTML('frontend needed '.$timeDiff.' seconds<br />');
	}
}