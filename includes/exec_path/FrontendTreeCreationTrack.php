<?php
require_once 'Track.php';
require_once __DIR__.'/../tree_creation/PkmnTreeRoot.php';
require_once __DIR__.'/../svg_creation/VisualNode.php';
require_once 'SVGCreationTrack.php';

class FrontendTreeCreationTrack extends Track {
	private $breedingTreeRoot;

	public function __construct (PkmnTreeRoot $breedingTreeRoot) {
		$this->breedingTreeRoot = $breedingTreeRoot;		
	}

	public function passOn (): string {
		$frontendRoot = $this->createFrontendRoot();
		$svgCreationTrack = new SVGCreationTrack($frontendRoot);
		return $svgCreationTrack->passOn(); 
	}

	private function createFrontendRoot (): VisualNode {
		Logger::statusLog('CREATING VISUALNODE INSTANCES');
		$timeStart = hrtime(true);

		$frontendRoot = new VisualNode($this->breedingTreeRoot);
		$frontendRoot->prep();
	
		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		Logger::outputDebugMessage('creating frontend pkmn tree needed: '.$timeDiff.'s');

		return $frontendRoot;
	}
}