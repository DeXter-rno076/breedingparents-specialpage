<?php
require_once 'OutputMessage.php';

class AlertMessage extends OutputMessage {
	private static $alreadyOutputtedOneTimeMessages = [];

	public function __construct (string $msg) {
		parent::__construct($msg);
	}

	public function outputOnce () {
		if ($this->oneTimeMessageGotAlreadyOutputted()) {
			return;
		}
		$this->addMessageToOneTimeMessageLog();
		$this->output();
	}

	private function oneTimeMessageGotAlreadyOutputted (): bool {
		return isset(AlertMessage::$alreadyOutputtedOneTimeMessages[$this->msg]);
	}

	private function addMessageToOneTimeMessageLog () {
		AlertMessage::$alreadyOutputtedOneTimeMessages[$this->msg] = 1;
	}

	protected function getMessageBoxCSSClasses (): string {
		return OutputMessage::STANDARD_BOX_CLASSES . ' breedingChainsAlertMessage';
	}
}