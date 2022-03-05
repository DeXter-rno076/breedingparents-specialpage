<?php
abstract class Pkmn {
	protected string $name;
	protected string $id;

	protected function __construct (string $name, string $id) {
		$this->name = $name;
		$this->id = $id;
	}

	public function getName (): string {
		return $this->name;
	}

	public function getID (): string {
		return $this->id;
	}

	public abstract function getLogInfo (): string;

	public function __toString (): string {
		return $this->getLogInfo();
	}
}