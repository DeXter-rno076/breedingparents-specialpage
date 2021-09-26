<?php
require_once 'FrontendPreparator.php';
require_once 'SVGHandler.php';
require_once __DIR__.'/../Constants.php';

class FrontendHandler {
	private $breedingTree = null;
	
	public function __construct (?BreedingChainNode $breedingTree) {
		$this->breedingTree = $breedingTree;
	}

	//adds svg tag to output (includes adding css and js files)
	//adds plain text responses for some cases
	public function addGraficOutput () {
		//for performance measuring
		$timeStart = hrtime(true);

		if ($this->breedingTree === null) {
			$this->addCantLearn();
		} else if (count($this->breedingTree->getSuccessors()) === 0) {
				$this->addCanLearnDirectly($this->breedingTree);
		} else {
			//preparator creates a tree structure with objects
			//	that have only the necessary infos for SVGHandler
			$preparator = new FrontendPreparator();
			$svgObjectStructure = $preparator->prepareForFrontend($this->breedingTree);

			$svgTagHeight = $svgObjectStructure->svgTagHeight;
			$svgHandler = new SVGHandler(
				$svgObjectStructure,
				$svgTagHeight,
			);
			$svgHandler->addOutput();
		}

		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		Constants::debug('frontend needed '.$timeDiff.' seconds');
	}

	private function addCanLearnDirectly (BreedingChainNode $breedingTree) {
		if ($breedingTree->getLearnsByEvent()) {
			if ($breedingTree->getLearnsByOldGen()) {
				Constants::out(Constants::$targetPkmn.' can learn '.Constants::$targetMove.' only via event learnsets from an older gen.');
			} else {
				Constants::out(Constants::$targetPkmn.' can learn '.Constants::$targetMove. ' directly only via event learnsets.');
			}
		} else if ($breedingTree->getLearnsByOldGen()) {
			Constants::out(Constants::$targetPkmn.' can learn '.Constants::$targetMove.' directly (level up, tmtr, tutor) in an old gen');	
		}
		else {
			Constants::out(Constants::$targetPkmn.' can learn '.Constants::$targetMove.' directly via level up, tutor or tmtp.');
		}
	}

	private function addCantLearn () {
		Constants::out(Constants::$targetPkmn.' cannot learn '.Constants::$targetMove);
	}
}