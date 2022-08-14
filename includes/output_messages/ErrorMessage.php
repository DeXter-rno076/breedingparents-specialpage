<?php
require_once __DIR__.'/../Constants.php';
require_once 'OutputMessage.php';

class ErrorMessage extends OutputMessage {
    private static $errorHappened = false;
    private static $alreadyOutputtedOneTimeMessages = [];

    public function __construct (string $eMsg) {
        ErrorMessage::$errorHappened = true;
        $errorMessageForOutput = $this->shortenErrorMessage($eMsg);
        $msg = Constants::i18nMsg('breedingchains-error', $errorMessageForOutput);
        parent::__construct($msg);
    }

    public static function constructWithError (Exception $e): ErrorMessage {
        return new ErrorMessage($e->__toString());
    }

    public static function errorHappened (): bool {
        return ErrorMessage::$errorHappened;
    }

    private function shortenErrorMessage (string $e): string {
        $wantedEndOfErrorMessage = $this->getWantedEndOfErrorMessage($e);
        return substr($e, 0, $wantedEndOfErrorMessage);
    }

    private function getWantedEndOfErrorMessage (string $eMsg): int {
        $msgEndMarker = ' in ';
        $msgEndMarkerIndex = strpos($eMsg, $msgEndMarker);
        if (!$msgEndMarkerIndex) {
            return strlen($eMsg);
        }
        return $msgEndMarkerIndex;
    }

    public function outputOnce () {
        if ($this->oneTimeMessageGotAlreadyOutputted()) {
            return;
        }
        $this->addMessageToOneTimeMessageLog();
        $this->output();
    }

    private function oneTimeMessageGotAlreadyOutputted (): bool {
        return isset(ErrorMessage::$alreadyOutputtedOneTimeMessages[$this->msg]);
    }

    private function addMessageToOneTimeMessageLog () {
        ErrorMessage::$alreadyOutputtedOneTimeMessages[$this->msg] = 1;
    }

    protected function getMessageBoxCSSClasses (): string {
        return OutputMessage::STANDARD_BOX_CLASSES . ' breedingChainsErrorMessage';
    }
}