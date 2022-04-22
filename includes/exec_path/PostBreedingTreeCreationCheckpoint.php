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
		}

		$this->reactToBreedingTreeState();

		if ($this->breedingTreeRoot->hasSuccessors()) {
			$frontendTreeCreationTrack = new FrontendTreeCreationTrack($this->breedingTreeRoot);
			return $frontendTreeCreationTrack->passOn();
		} else {
			return $this->terminationCode;
		}
	}

	private function breedingTreeIsEmpty (): bool {
		return is_null($this->breedingTreeRoot);
	}

	private function reactToEmptyBreedingTree () {
		$this->outputInfoMessage('breedingchains-cant-learn', Constants::$targetPkmnNameNormalCasing,
			Constants::$targetMoveNameNormalCasing);
		Logger::elog('empty breeding tree, the tree must at least contain the root');
	}

	private function reactToBreedingTreeState () {
		$msgIdentifiers = [];
		if ($this->breedingTreeRoot->learnsDirectly()) {
			$msgIdentifiers[] = $this->getLearnsDirectlyMsgIdentifier();
		}
		if (!$this->breedingTreeRoot->hasSuccessors() && $this->breedingTreeRoot->canInherit()) {
			$msgIdentifiers[] = 'breedingchains-can-inherit-but-no-successors';
		}
		if ($this->breedingTreeRoot->learnsByEvent()) {
			$msgIdentifiers[] = 'breedingchains-can-learn-event';
		}
		if ($this->breedingTreeRoot->learnsByOldGen()) {
			$msgIdentifiers[] = 'breedingchains-can-learn-oldgen';
		}

		foreach ($msgIdentifiers as $msgIdentifier) {
			$this->outputInfoMessage($msgIdentifier, Constants::$targetPkmnNameNormalCasing,
				Constants::$targetMoveNameNormalCasing);
		}
	}
	private function getLearnsDirectlyMsgIdentifier (): string {
		if (Constants::$targetGenNumber < 8) {
			return 'breedingchains-can-learn-directly-old-gen';
		} else {
			return 'breedingchains-can-learn-directly-new-gen';
		}
	}
}