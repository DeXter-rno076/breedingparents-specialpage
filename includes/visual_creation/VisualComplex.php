<?php
abstract class VisualComplex {
    public abstract function compile (int $xOffset, int $yOffset): array;

    public abstract function getLogInfo (): string;

    public function __toString (): string {
        return $this->getLogInfo();
    }
}