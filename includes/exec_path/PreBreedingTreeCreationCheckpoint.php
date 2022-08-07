<?php
require_once 'Checkpoint.php';
require_once __DIR__.'/../Constants.php';
require_once 'BreedingTreeCreationTrack.php';

class PreBreedingTreeCreationCheckpoint extends Checkpoint {
    public function __construct () {
        parent::__construct('unknown pkmn name');
    }

    public function passOn (): string {
        //todo check whether move has a typo or generally if it's a move
        if ($this->targetPkmnNameIsUnknown()) {
            $this->outputTargetPkmnNameIsUnknownMsg();
            return $this->terminationCode;
        }

        $breedingTreeCreationTrack = new BreedingTreeCreationTrack();
        return $breedingTreeCreationTrack->passOn();
    }

    private function targetPkmnNameIsUnknown (): bool {
        $pkmnName = Constants::$targetPkmnName;
        return !isset(Constants::$externalPkmnGenCommons->$pkmnName);
    }

    private function outputTargetPkmnNameIsUnknownMsg () {
        $this->outputInfoMessage('breedingchains-unknown-pkmn',
            Constants::$targetPkmnNameOriginalInput);
    }
}