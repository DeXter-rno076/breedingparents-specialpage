<?php
require_once __DIR__.'/../Logger.php';
require_once 'SVGElement.php';
require_once 'SVGPkmn.php';

class SVGTag extends SVGElement {
    private string $id = 'breedingParentsSVG';
    private int $width;
    private int $height;
    private SVGPkmn $svgRoot;

    public function __construct (FrontendPkmn $pkmnRoot) {
        parent::__construct('svg');
        Logger::statusLog('creating SVGRoot instance');
        
        $treeDepth = $pkmnRoot->getDepth();
        $this->width = $treeDepth * Constants::PKMN_MARGIN_HORI 
            + Constants::SVG_SAFETY_MARGIN;
        $this->height = $pkmnRoot->getTreeSectionHeight() 
            + Constants::SVG_SAFETY_MARGIN;

        $this->svgRoot = new SVGPkmn($pkmnRoot);
    }

    public function toHTMLString (int $offset): string {
        $outputString = '<svg id="'.$this->id.
            '" width="'.$this->width.'" height="'.$this->height.'">';
        $outputString .= $this->svgRoot->toHTMLString($offset);
        $outputString .= '</svg>';
        return $outputString;
    }

    public function getLogInfo (): string {
        return 'SVGTag:;;';
    }
}