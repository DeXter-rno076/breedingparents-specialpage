<?php
require_once 'ExecPathPart.php';
require_once __DIR__.'/../output_messages/InfoMessage.php';
require_once __DIR__.'/../output_messages/ErrorMessage.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../Constants.php';

abstract class Checkpoint extends ExecPathPart {
    protected $terminationCode;
    protected $errorCode = 'unexpected error';

    protected function __construct (string $terminationCode) {
        $this->terminationCode = $terminationCode;
    }

    protected function outputInfoMessage (string $msgIdentifier, string ...$params) {
        $messageBox = new InfoMessage(Constants::i18nMsg($msgIdentifier, $params));
        $messageBox->output();
    }

    protected function checkForErrors (): bool {
        if (Logger::erroHappened()) {
            $errorLogs = Logger::getELogs();
            $errorString = join('<br />', $errorLogs);
            $eMsg = new ErrorMessage($errorString);
            $eMsg->output();
            return true;
        }
        if (ErrorMessage::errorHappened()) {
            return true;
        }

        return false;
    }
}