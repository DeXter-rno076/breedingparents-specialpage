<?php
require_once 'Checkpoint.php';
require_once __DIR__.'/../tree_creation/PkmnTreeRoot.php';
require_once __DIR__.'/../tree_creation/BreedingRootSubtree.php';
require_once __DIR__.'/../Constants.php';
require_once 'FrontendTreeCreationTrack.php';

class PostBreedingTreeCreationCheckpoint extends Checkpoint {
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

        if ($this->breedingTreeRoot->hasSuccessors()) {
            $frontendTreeCreationTrack = new FrontendTreeCreationTrack($this->breedingTreeRoot);
            return $frontendTreeCreationTrack->passOn();
        } else {

            return $this->terminationCode;
        }
    }
}