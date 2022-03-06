<?php
require_once 'OutputMessage.php';

class InfoMessage extends OutputMessage {
	private static array $alreadyOutputtedOneTimeMessages = [];

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
		return isset(InfoMessage::$alreadyOutputtedOneTimeMessages[$this->msg]);
	}

	private function addMessageToOneTimeMessageLog () {
		InfoMessage::$alreadyOutputtedOneTimeMessages[$this->msg] = 1;
	}

	protected function getMessageBoxCSSClasses (): string {
		return OutputMessage::STANDARD_BOX_CLASSES . ' breedingChainsInfoMessage';
	}
}