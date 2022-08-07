<?php
require_once __DIR__.'/../HTMLElement.php';

abstract class SVGElement {
    protected $tagType;
    protected $groupId = null;

    protected function __construct (string $tagType, int $groupId) {
        $this->tagType = $tagType;
        $this->groupId = $groupId;
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