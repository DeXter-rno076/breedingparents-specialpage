<?php
require_once 'Checkpoint.php';
require_once __DIR__.'/../tree_creation/BreedingTreeNode.php';
require_once __DIR__.'/../Constants.php';
require_once 'FrontendTreeCreationTrack.php';

class PostBreedingTreeCreationCheckpoint extends Checkpoint {
	private $breedingTreeRoot;
	
	public function __construct (?BreedingTreeNode $breedingTreeRoot) {
		parent::__construct('non standard breeding tree');
		$this->breedingTreeRoot = $breedingTreeRoot;
	}

	public function passOn (): string {
		if ($this->breedingTreeIsEmpty()) {
			$this->reactToEmptyBreedingTree();
			return $this->terminationCode;
		} else if ($this->breedingTreeRootHasNoSuccessors()) {
			$this->reactToBreedingTreeRootHasNoSuccessors();
			return $this->terminationCode;
		} else {
			$this->reactToNormalBreedingTree();
		}

		$frontendTreeCreationTrack = new FrontendTreeCreationTrack($this->breedingTreeRoot);
		return $frontendTreeCreationTrack->passOn();
	}

	private function breedingTreeIsEmpty (): bool {
		return is_null($this->breedingTreeRoot);
	}

	private function reactToEmptyBreedingTree () {
		$this->outputInfoMessage('breedingchains-cant-learn', Constants::$targetPkmnNameNormalCasing,
			Constants::$targetMoveNameNormalCasing);
	}

	private function breedingTreeRootHasNoSuccessors (): bool {
		return !$this->breedingTreeRoot->hasSuccessors();
	}

	private function reactToBreedingTreeRootHasNoSuccessors () {
		//todo if a lowest evo can inherit the move but no suiting parents are found, this wouldnt be handled
		$msgIdentifier = $this->getTreeHasNoSuccessorsMsgIdentifier();
		$this->outputInfoMessage($msgIdentifier, Constants::$targetPkmnNameNormalCasing,
			Constants::$targetMoveNameNormalCasing);
	}

	private function getTreeHasNoSuccessorsMsgIdentifier (): string {
		if ($this->breedingTreeRoot->getLearnsByEvent()) {
			return 'breedingchains-can-learn-event';
		} else if ($this->breedingTreeRoot->getLearnsByOldGen()) {
			return 'breedingchains-can-learn-oldgen';
		} else {
			return 'breedingchains-can-learn-directly';
		}
	}

	private function reactToNormalBreedingTree () {
		//todo check for direct learnability
	}
}