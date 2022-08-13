<?php
require_once __DIR__.'/../../Constants.php';
require_once __DIR__.'/../VisualSubtree.php';
require_once __DIR__.'/../VisualRoot.php';
require_once __DIR__.'/../VisualPreparationSubtree.php';
require_once 'JSONElement.php';
require_once 'JSONSubtree.php';

class JSONRoot extends VisualRoot implements JSONElement {
    protected function instantiateVisualSubtree(VisualPreparationSubtree $pkmnRoot): VisualSubtree {
        return new JSONSubtree($pkmnRoot);
    }

    protected function calculateHeight (int $treeSectionHeight): int {
        return $treeSectionHeight;
    }

    protected function calculateWidth (int $treeDepth): int {
        return ($treeDepth - 1)*Constants::VISUAL_NODE_MARGIN_HORIZONTAL;
    }

    public function compile (
        int $xOffset = 0,
        int $yOffset = 0
    ): array {
        $root = [
            'tag' => 'root',
            'id' => 'breedingChainsSVG',
            'xmlns' => 'http://www.w3.org/2000/svg',
            'viewbox' => '0 0 '.$this->width.' '.$this->height,
            'groupid' => $this->groupId,
            'innerContent' => []
        ];

        $topLevelSVGTags = $this->tree->compile($xOffset, $yOffset);

        foreach ($topLevelSVGTags as $tag) {
            $root['innerContent'][] = $tag;
        }

        return $root;
    }

    public function getLogInfo (): string {
        return '\'\'\'JSONRoot\'\'\':;;';
    }
}