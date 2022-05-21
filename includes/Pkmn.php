<?php
/**
 * todo rename -> there are non pkmn entities in the trees
 */
abstract class Pkmn {
	protected $name;

	protected function __construct (string $name) {
		$this->name = $name;
	}

	public function getName (): string {
		return $this->name;
	}

	public function is (string $name): bool {
		return strtolower($this->name) === strtolower($name);
	}

	public abstract function getLogInfo (): string;

	public function __toString (): string {
		return $this->getLogInfo();
	}
}