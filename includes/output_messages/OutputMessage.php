<?php
require_once __DIR__.'/../HTMLElement.php';

abstract class OutputMessage {
	protected $msg;
	public const STANDARD_BOX_CLASSES = 'breedingChainsMessageBox';

	public function __construct ($msg) {
		$this->msg = (string) $msg;
	}

	public function output () {
		$box = $this->createBox();
		$this->addBoxToOutput($box);
	}

	/**
	 * Is abstract because handling the static arrays in subclasses didn't work.
	 * I first tried to implement outputOnce in the super class with an abstract 
	 * function, that returns the static array, but changes to the arrays were ignored.
	 */
	public abstract function outputOnce ();

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

	public function __toString (): string {
		return $this->msg;
	}
}