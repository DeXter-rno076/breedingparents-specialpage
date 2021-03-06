<?php
require 'FrontendPreparator.php';
require 'SVGHandler.php';

class FrontendHandler {
	private $breedingTree = null;
	private $pkmnData = null;
	private $PKMN_ICON_HEIGHT = 50;
	
	public function __construct ($breedingTree, $pkmnData) {
		$this->breedingTree = $breedingTree;
		$this->pkmnData = $pkmnData;
	}

	public function addSVG ($output) {
		$preparator = new FrontendPreparator($this->pkmnData, $this->PKMN_ICON_HEIGHT);
		$svgObjectStructure = $preparator->prepareForFrontend($this->breedingTree);

		$generalHeight = $svgObjectStructure->generalHeight;
		$svgHandler = new SVGHandler($svgObjectStructure, $generalHeight, $this->PKMN_ICON_HEIGHT);
		$svgHandler->addOutput($output);
	}
}
?>