<?php
require_once __DIR__.'/../Logger.php';

abstract class SVGElement {
    protected string $tagType;

    protected function __construct (string $tagType) {
        $this->tagType = $tagType;
    }

    public function getTagType (): string {
        return $this->tagType;
    }

    public abstract function toHTMLString (): string;

    public abstract function getLogInfo (): string;

    public function __toString (): string {
        return $this->getLogInfo();
    }
}