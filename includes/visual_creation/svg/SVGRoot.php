<?php
require_once 'SVGElement.php';
require_once 'SVGSubtree.php';
require_once __DIR__.'/../../Constants.php';
require_once __DIR__.'/../VisualRoot.php';
require_once __DIR__.'/../../HTMLElement.php';

require_once __DIR__.'/../VisualPreparationSubtree.php';
require_once __DIR__.'/../VisualSubtree.php';

class SVGRoot extends VisualRoot implements SVGElement {
    protected function instantiateVisualSubtree(VisualPreparationSubtree $pkmnRoot): VisualSubtree {
        return new SVGSubtree($pkmnRoot);
    }

    protected function calculateHeight (int $treeSectionHeight): int {
        return $treeSectionHeight + Constants::SVG_OFFSET + Constants::SVG_SAFETY_MARGIN;
    }

    protected function calculateWidth (int $treeDepth): int {
        return ($treeDepth - 1)*Constants::VISUAL_NODE_MARGIN_HORIZONTAL + Constants::SVG_OFFSET
            + Constants::SVG_SAFETY_MARGIN;
    }

    public function compile (
        int $xOffset = Constants::SVG_OFFSET,
        int $yOffset = Constants::SVG_OFFSET
    ): HTMLElement {
        $svgTag = new HTMLElement('svg', [
            'id' => 'breedingChainsSVG',
            'xmlns' => 'http://www.w3.org/2000/svg',
            'viewbox' => '0 0 '.$this->width.' '.$this->height,
            'groupid' => $this->groupId
        ]);

        $topLevelSVGTags = $this->tree->compile($xOffset, $yOffset);

        foreach ($topLevelSVGTags as $tag) {
            $svgTag->addInnerElement($tag);
        }

        return $svgTag;
    }

    public function getLogInfo (): string {
        return '\'\'\'SVGRoot\'\'\':;;';
    }
}