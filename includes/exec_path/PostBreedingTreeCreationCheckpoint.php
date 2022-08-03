<?php
require_once 'Checkpoint.php';
require_once __DIR__.'/../tree_creation/PkmnTreeRoot.php';
require_once __DIR__.'/../tree_creation/BreedingRootSubtree.php';
require_once __DIR__.'/../Constants.php';
require_once 'FrontendTreeCreationTrack.php';

class PostBreedingTreeCreationCheckpoint extends Checkpoint {
	private $breedingTreeRootSubtree;
	private $breedingTreeRootLearnabilityStatus;
	
	public function __construct (BreedingRootSubtree $breedingTreeRoot) {
		parent::__construct('non standard breeding tree');
		$this->breedingTreeRoot = $breedingTreeRoot;
		$this->breedingTreeRootLearnabilityStatus = $breedingTreeRoot->getRoot()->getLearnabilityStatus();
	}

	public function passOn (): string {
		if (!$this->breedingTreeRootLearnabilityStatus->canLearn()) {
			$this->outputInfoMessage('breedingchains-cant-learn',
				Constants::$targetPkmnNameOriginalInput, Constants::$targetMoveNameOriginalInput);
			return $this->terminationCode;
		}
		$this->reactToNonTerminatingBreedingTreeState();

		if ($this->breedingTreeRoot->hasSuccessors()) {
			$frontendTreeCreationTrack = new FrontendTreeCreationTrack($this->breedingTreeRoot);
			return $frontendTreeCreationTrack->passOn();
		} else {
			
			return $this->terminationCode;
		}
	}

	private function reactToNonTerminatingBreedingTreeState () {
		$msgIdentifiers = [];
		if ($this->breedingTreeRootLearnabilityStatus->getLearnsDirectly()) {
			$msgIdentifiers[] = $this->getLearnsDirectlyMsgIdentifier();
		}
		if (!$this->breedingTreeRoot->hasSuccessors() 
				&& $this->breedingTreeRootLearnabilityStatus->getLearnsByBreeding()) {
			$msgIdentifiers[] = 'breedingchains-can-inherit-but-no-successors';
		}
		if ($this->breedingTreeRootLearnabilityStatus->getLearnsByEvent()) {
			$msgIdentifiers[] = 'breedingchains-can-learn-event';
		}
		if ($this->breedingTreeRootLearnabilityStatus->getLearnsByOldGen()) {
			$msgIdentifiers[] = 'breedingchains-can-learn-oldgen';
		}

		foreach ($msgIdentifiers as $msgIdentifier) {
			$this->outputInfoMessage($msgIdentifier, Constants::$targetPkmnNameOriginalInput,
				Constants::$targetMoveNameOriginalInput);
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