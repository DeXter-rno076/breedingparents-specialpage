<?php
require_once __DIR__.'/../Logger.php';

class FileNotFoundException extends Exception {
	private $msg;
	private $pkmnId;

	public function __construct (string $pkmnId, Throwable $previous = null) {
		$this->msg = 'couldn\'t load pkmn icon of '.$pkmnId;
		$this->pkmnId = $pkmnId;
		parent::__construct($this->msg, 0, $previous);

		Logger::elog($this->__toString());
	}

	public function __toString (): string {
		return 'FileNotFoundException: '.$this->msg;
	}

	public function getPkmnId (): string {
		return $this->pkmnId;
	}
}