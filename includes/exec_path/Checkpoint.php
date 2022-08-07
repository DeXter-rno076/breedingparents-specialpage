<?php
require_once 'ExecPathPart.php';
require_once __DIR__.'/../output_messages/InfoMessage.php';
require_once __DIR__.'/../Constants.php';

abstract class Checkpoint extends ExecPathPart {
    protected $terminationCode;

    protected function __construct (string $terminationCode) {
        $this->terminationCode = $terminationCode;
    }

    protected function outputInfoMessage (string $msgIdentifier, string ...$params) {
        $messageBox = new InfoMessage(Constants::i18nMsg($msgIdentifier, $params));
        $messageBox->output();
    }
}