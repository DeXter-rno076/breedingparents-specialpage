<?php
interface JSONElement {
    function compile (int $xOffset, int $yOffset): array;
}