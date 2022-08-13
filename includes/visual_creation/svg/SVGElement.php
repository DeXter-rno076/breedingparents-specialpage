<?php
require_once __DIR__.'/../../HTMLElement.php';

interface SVGElement {
    function compile (int $xOffset, int $yOffset): HTMLElement;
}