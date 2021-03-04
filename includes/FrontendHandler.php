<?php
require 'FrontendPreparator.php';
require 'SVGHandler.php';

class FrontendHandler {
	private $breedingTree = null;
	
	public function __construct ($breedingTree) {
		$this->breedingTree = $breedingTree;
	}

	public function addSVG ($output) {
		$preparator = new FrontendPreparator($this->breedingTree);
		$svgObjectStructure = $preparator->prepareForFrontend($this->breedingTree);

		$maxDeepness = $svgObjectStructure->maxDeepness;
		$generalHeight = $svgObjectStructure->height;
		$svgHandler = new SVGHandler($this->breedingTree, $maxDeepness, $output, $generalHeight);
	}
}
?>