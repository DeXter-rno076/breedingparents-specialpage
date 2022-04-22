<?php
require_once 'Track.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../tree_creation/BreedingTreeNode.php';
require_once 'PostBreedingTreeCreationCheckpoint.php';

class BreedingTreeCreationTrack extends Track {
	public function passOn (): string {
		$breedingTreeRoot = $this->createBreedingTree();
		$postBreedingTreeCreationCheckpoint = new PostBreedingTreeCreationCheckpoint($breedingTreeRoot);
		return $postBreedingTreeCreationCheckpoint->passOn();
	}

	private function createBreedingTree (): ?BreedingTreeNode {
		Logger::statusLog('CREATING BREEDING TREE NODES');
		$timeStart = hrtime(true);

		$breedingTreeRoot = new BreedingTreeNode(Constants::$targetPkmnName, true);
		$breedingTreeRoot = $breedingTreeRoot->createBreedingTreeNode([]);

		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		Logger::outputDebugMessage('breeding tree creation needed: '.$timeDiff.'s');

		return $breedingTreeRoot;
	}
}