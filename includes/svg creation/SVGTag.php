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
        $this->width = ($treeDepth - 1) * Constants::PKMN_MARGIN_HORI + Constants::SVG_OFFSET
            + Constants::SVG_SAFETY_MARGIN;
        $this->height = $pkmnRoot->getTreeSectionHeight() + Constants::SVG_OFFSET
            + Constants::SVG_SAFETY_MARGIN;

        $this->svgRoot = new SVGPkmn($pkmnRoot);
    }

    public function toHTML (
        int $xOffset = Constants::SVG_OFFSET,
        int $yOffset = Constants::SVG_OFFSET
    ): HTMLElement {
        $svgTag = new HTMLElement('svg', [
            'id' => $this->id,
            'xmlns' => 'http://www.w3.org/2000/svg',
            'width' => $this->width,
            'height' => $this->height
        ]);
        
        $topLevelSVGTags = $this->svgRoot->toHTML($xOffset, $yOffset);

        foreach ($topLevelSVGTags as $tag) {
            $svgTag->addInnerElement($tag);
        }

        if (Constants::$displayDebuglogs) {
            //zoom debugging
            $centerMarker = new HTMLElement('circle', [
                'cx' => $this->width / 2,
                'cy' => $this->height / 2,
                'r' => 5,
                'style' => 'fill:red'
            ]);
            $svgTag->addInnerElement($centerMarker);
        }

        return $svgTag;
    }

    public function getLogInfo (): string {
        return '\'\'\'SVGTag\'\'\':;;';
    }
}