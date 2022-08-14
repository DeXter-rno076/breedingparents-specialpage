<?php
require_once 'Checkpoint.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../output_messages/ErrorMessage.php';
require_once __DIR__.'/../Constants.php';
require_once 'BreedingTreeCreationTrack.php';

class PreBreedingTreeCreationCheckpoint extends Checkpoint {
    public function __construct () {
        parent::__construct('unsuiting pkmn selection');
    }

    public function passOn (): string {
        if ($this->checkForErrors()) {
            return $this->errorCode;
        }

        //todo check whether move has a typo or generally if it's a move
        if ($this->targetPkmnNameIsUnknown()) {
            $this->outputTargetPkmnNameIsUnknownMsg();
            return $this->terminationCode;
        }

        if ($this->targetPkmnDoesntExistInTargetGame()) {
            $this->outputTargetPkmnDoesntExistMsg();
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

    private function targetPkmnDoesntExistInTargetGame (): bool {
        $pkmnName = Constants::$targetPkmnName;
        try {
            $pkmnData = PkmnData::cachedConstruct($pkmnName);
            return !$pkmnData->existsInThisGame();
        } catch (Exception $e) {
            $eMsg = ErrorMessage::constructWithError($e);
            $eMsg->output();
            return true;
        }
    }

    private function outputTargetPkmnDoesntExistMsg () {
        $this->outputInfoMessage('breedingchains-pkmn-doesnt-exist',
            Constants::$targetPkmnNameOriginalInput, Constants::$targetGameOriginalInput);
    }
}