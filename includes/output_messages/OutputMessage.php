<?php

require_once __DIR__.'/../HTMLElement.php';

abstract class OutputMessage {
	protected string $msg;
	public const STANDARD_BOX_CLASSES = 'breedingChainsMessageBox';

	protected static $alreadyOutputtedOneTimeMessages = [];

	public function __construct ($msg) {
		$this->msg = (string) $msg;
	}

	public function output () {
		$box = $this->createBox();
		$this->addBoxToOutput($box);
	}

	public function outputOnce () {
		if ($this->oneTimeMessageGotAlreadyOutputted()) {
			return;
		}
		$this->addMessageToOneTimeMessageLog();
		$this->output();
	}

	private function oneTimeMessageGotAlreadyOutputted (): bool {
		return isset($this->getOneTimeMessageOutputLog[$this->msg]);
	}

	protected abstract function getOneTimeMessageOutputLog (): array;

	private function addMessageToOneTimeMessageLog () {
		$this->getOneTimeMessageOutputLog[$this->msg] = 1;
	}

	private function addBoxToOutput (HTMLElement $box) {
		$box->addToOutput();
	}

	protected function createBox (): HTMLElement {
		$box = new HTMLElement('div', [
			'class' => $this->getMessageBoxCSSClasses()
		], [
			$this->msg
		]);
		return $box;
	}

	protected abstract function getMessageBoxCSSClasses (): string;
}