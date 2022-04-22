<?php
require_once 'Track.php';
require_once __DIR__.'/../tree_creation/BreedingTreeNode.php';
require_once __DIR__.'/../svg_creation/FrontendPkmn.php';
require_once 'SVGCreationTrack.php';

class FrontendTreeCreationTrack extends Track {
	private $breedingTreeRoot;

	public function __construct (BreedingTreeNode $breedingTreeRoot) {
		$this->breedingTreeRoot = $breedingTreeRoot;		
	}

	public function passOn (): string {
		$frontendRoot = $this->createFrontendRoot();
		$svgCreationTrack = new SVGCreationTrack($frontendRoot);
		return $svgCreationTrack->passOn(); 
	}

	private function createFrontendRoot (): FrontendPkmn {
		Logger::statusLog('CREATING FRONTENDPKMN INSTANCES');
		$timeStart = hrtime(true);

		$frontendRoot = new FrontendPkmn($this->breedingTreeRoot);
		$frontendRoot->setTreeIconsAndCoordinates();
	
		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		Logger::outputDebugMessage('creating frontend pkmn tree needed: '.$timeDiff.'s');

		return $frontendRoot;
	}
}