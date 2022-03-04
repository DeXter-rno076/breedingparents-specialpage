<?php
require_once 'OutputMessage.php';

class InfoMessage extends OutputMessage {
	public function __construct (string $msg) {
		parent::__construct($msg);
	}

	protected function getOneTimeMessageOutputLog (): array {
		return InfoMessage::$alreadyOutputtedOneTimeMessages;
	}

	protected function getMessageBoxCSSClasses (): string {
		return OutputMessage::STANDARD_BOX_CLASSES . ' breedingChainsInfoMessage';
	}
}