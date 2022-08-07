<?php
require_once 'Track.php';
require_once 'PreDataLoadingCheckpoint.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../output_messages/InfoMessage.php';

class ConstantsInitializationTrack extends Track {
    private $formData;

    public function __construct (array $formData) {
        $this->formData = $formData;
    }

    public function passOn (): string {
        $initFailed = $this->initConstants();
        if ($initFailed) {
            return 'constants initialization failed';
        }

        $preDataLoadingCheckpoint = new PreDataLoadingCheckPoint();
        return $preDataLoadingCheckpoint->passOn();
    }

    private function initConstants (): bool {
        //todo i dont like these magic strings
        Constants::$GAME_LIST = json_decode(file_get_contents(__DIR__.'/../../manual_data/gamesToSk.json'));
        Constants::$GAMES_TO_GEN = json_decode(file_get_contents(__DIR__.'/../../manual_data/gamesToGen.json'));
        Constants::$MOVE_NAMES = json_decode(file_get_contents(__DIR__.'/../../manual_data/moveNames.json'));
        Constants::$MOVE_NAME_TO_NEW_MOVE_NAME = json_decode(file_get_contents(__DIR__.'/../../manual_data/renamedMoves.json'));

        $gameInput = trim($this->formData['targetGame']);
        if (!isset(Constants::$GAME_LIST->$gameInput)) {
            $infoMsg = new InfoMessage(Constants::i18nMsg('breedingchains-unknown-game', $gameInput));
            $infoMsg->output();
            return true;
        }
        $targetGameString = Constants::$GAME_LIST->$gameInput;
        Constants::$targetGameString = $targetGameString;
        Constants::$targetGenNumber = Constants::$GAMES_TO_GEN->$targetGameString;

        Constants::$targetMoveNameOriginalInput = trim($this->formData['targetMove']);
        Constants::$targetMoveName = $this->buildInternalMoveName(Constants::$targetMoveNameOriginalInput);

        Constants::$targetPkmnNameOriginalInput = trim($this->formData['targetPkmn']);
        Constants::$targetPkmnName = mb_strtolower(Constants::$targetPkmnNameOriginalInput);

        Constants::$displayDebuglogs = isset($this->formData['displayDebugLogs']);
        Constants::$displayStatuslogs = isset($this->formData['displayStatusLogs']);
        Constants::$createDetailedSuccessorFilterLogs = isset($this->formData['createDetailedSuccessorFilterLogs']);

        Constants::logUserinputConstants();

        return false;
    }

    private function buildInternalMoveName (string $moveInput): string {
        $internalMoveName = trim($moveInput);
        if (isset(Constants::$MOVE_NAME_TO_NEW_MOVE_NAME->$internalMoveName)) {
            $internalMoveName = Constants::$MOVE_NAME_TO_NEW_MOVE_NAME->$internalMoveName;
        }
        return mb_strtolower($internalMoveName);
    }
}