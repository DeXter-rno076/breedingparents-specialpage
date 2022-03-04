<?php

require_once 'OutputMessage.php';

class ErrorMessage extends OutputMessage {
	public function __construct (Exception $e) {
		$errorMessageForOutput = $this->shortenErrorMessage((string) $e);
		$msg = Constants::i18nMsg('breedingchains-error', $errorMessageForOutput);
		parent::__construct($msg);
	}

	private function shortenErrorMessage (string $e): string {
		$wantedEndOfErrorMessage = $this->getWantedEndOfErrorMessage($e);
		return substr($e, 0, $wantedEndOfErrorMessage);
	}

	private function getWantedEndOfErrorMessage (string $eMsg): int {
		$msgEndMarker = 'in';
		$msgEndMarkerIndex = strpos($eMsg, $msgEndMarker);
		if (!$msgEndMarkerIndex) {
			return strlen($eMsg);
		}
		return $msgEndMarkerIndex;
	}

	protected function getOneTimeMessageOutputLog (): array {
		return ErrorMessage::$alreadyOutputtedOneTimeMessages;
	}

	protected function getMessageBoxCSSClasses (): string {
		return OutputMessage::STANDARD_BOX_CLASSES . ' breedingChainsErrorMessage';
	}
}