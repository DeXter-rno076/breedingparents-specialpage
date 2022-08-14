<?php
require_once 'Checkpoint.php';
require_once 'ConstantsInitializationTrack.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../output_messages/AlertMessage.php';

class FormValidationCheckpoint extends Checkpoint {
    private $formData;

    public function __construct (array $formData) {
        $this->formData = $formData;
    }

    public function passOn (): string {
        if ($this->checkForErrors()) {
            return $this->errorCode;
        }

        if ($this->formIsEmpty()) {
            return 'form is empty';
        }
        if ($this->formIsUnclean()) {
            return 'unclean form';
        }
        $constantsInitializationTrack = new ConstantsInitializationTrack($this->formData);
        return $constantsInitializationTrack->passOn();
    }

    private function formIsEmpty (): bool {
        $fd = $this->formData;
        return !isset($fd['targetGame']) || !isset($fd['targetPkmn']) || !isset($fd['targetMove']);
    }

    private function formIsUnclean (): bool {
        $gameInputUncleanStatus = $this->checkGameInput();
        $pkmnInputUncleanStatus = $this->checkPkmnInput();
        $moveInputUncleanStatus = $this->checkMoveInput();

        return $gameInputUncleanStatus || $pkmnInputUncleanStatus || $moveInputUncleanStatus;
    }

    private function checkGameInput (): bool {
        $gameInput = trim($this->formData['targetGame']);
        $regex = '/[^a-zA-Z:\'ßẞüÜ2é ]/';

        if (preg_match($regex, $gameInput)) {
            $alertMessage = new AlertMessage(Constants::i18nMsg('breedingchains-invalid-game'));
            $alertMessage->outputOnce();
            return true;
        } else {
            return false;
        }
    }

    private function checkPkmnInput (): bool {
        $pkmnInput = trim($this->formData['targetPkmn']);
        $regex = '/[^a-zA-Zßäéü\-♂♀2:\s]/';
        if (preg_match($regex, $pkmnInput)) {
            $alertMessage = new AlertMessage(Constants::i18nMsg('breedingchains-invalid-pkmn'));
            $alertMessage->outputOnce();
            return true;
        } else {
            return false;
        }
    }

    private function checkMoveInput (): bool {
        $moveInput = trim($this->formData['targetMove']);
        $regex = '/[^a-zA-ZÜßäöü\- 2\s]/';
        if (preg_match($regex, $moveInput)) {
            $alertMessage = new AlertMessage(Constants::i18nMsg('breedingchains-invalid-move'));
            $alertMessage->outputOnce();
            return true;
        } else {
            return false;
        }
    }
}