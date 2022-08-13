<?php
abstract class VisualElement {
    protected $tagType;
    protected $groupId = null;

    protected function __construct (string $tagType, int $groupId) {
        $this->tagType = $tagType;
        $this->groupId = $groupId;
    }

    public function getTagType (): string {
        return $this->tagType;
    }

    public abstract function getLogInfo (): string;

    public abstract function compile (int $xOffset, int $yOffset);

    public function __toString (): string {
        return $this->getLogInfo();
    }
}