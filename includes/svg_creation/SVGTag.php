<?php
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';
require_once __DIR__.'/../Constants.php';

require_once 'SVGElement.php';
require_once 'SVGSubtree.php';
require_once 'VisualSubtree.php';

class SVGTag extends SVGElement {
    private $id = 'breedingChainsSVG';
    private $width;
    private $height;
    private $svgTree;

    public function __construct (VisualSubtree $pkmnRoot, int $groupId) {
        parent::__construct('svg', $groupId);
        Logger::statusLog('creating SVGRoot instance');

        $this->width = $this->calculateWidth($pkmnRoot->getDepth());
        $this->height = $this->calculateHeight($pkmnRoot->getSubtreeHeight());

        $this->svgTree = new SVGSubtree($pkmnRoot);
    }

    private function calculateWidth (int $treeDepth): int {
        return ($treeDepth - 1) * Constants::PKMN_MARGIN_HORIZONTAL + Constants::SVG_OFFSET
        + Constants::SVG_SAFETY_MARGIN;
    }

    private function calculateHeight (int $treeSectionHeight): int {
        return $treeSectionHeight + Constants::SVG_OFFSET + Constants::SVG_SAFETY_MARGIN;
    }

    public function toHTML (
        int $xOffset = Constants::SVG_OFFSET,
        int $yOffset = Constants::SVG_OFFSET
    ): HTMLElement {
        $svgTag = new HTMLElement('svg', [
            'id' => $this->id,
            'xmlns' => 'http://www.w3.org/2000/svg',
            'viewbox' => '0 0 '.$this->width.' '.$this->height,
            'groupid' => $this->groupId
        ]);

        $topLevelSVGTags = $this->svgTree->toHTMLElements($xOffset, $yOffset);

        foreach ($topLevelSVGTags as $tag) {
            $svgTag->addInnerElement($tag);
        }

        return $svgTag;
    }

    public function getLogInfo (): string {
        return '\'\'\'SVGTag\'\'\':;;';
    }
}