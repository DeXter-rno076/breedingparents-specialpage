<?php
require_once __DIR__.'/../HTMLElement.php';

abstract class SVGElement {
	protected $tagType;

	protected function __construct (string $tagType) {
		$this->tagType = $tagType;
	}

	public function getTagType (): string {
		return $this->tagType;
	}

	public abstract function toHTML (int $xOffset, int $yOffset): HTMLElement;

	public abstract function getLogInfo (): string;

	public function __toString (): string {
		return $this->getLogInfo();
	}
}