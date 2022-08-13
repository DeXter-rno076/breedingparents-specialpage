<?php
require_once 'Track.php';
require_once __DIR__.'/../tree_creation/BreedingRootSubtree.php';
require_once __DIR__.'/../visual_creation/VisualPreparationSubtree.php';
require_once __DIR__.'/../Logger.php';
require_once 'SVGCreationTrack.php';

class FrontendTreeCreationTrack extends Track {
    private $breedingTreeRoot;

    public function __construct (BreedingRootSubtree $breedingTreeRoot) {
        $this->breedingTreeRoot = $breedingTreeRoot;
    }

    public function passOn (): string {
        $frontendRoot = $this->createFrontendRoot();
        $svgCreationTrack = new SVGCreationTrack($frontendRoot);
        return $svgCreationTrack->passOn();
    }

    private function createFrontendRoot (): VisualPreparationSubtree {
        Logger::statusLog('PREPARING VISUAL TREE');
        $timeStart = hrtime(true);

        $visualRoot = new VisualPreparationSubtree($this->breedingTreeRoot);
        $visualRoot->prep();

        $timeEnd = hrtime(true);
        $timeDiff = ($timeEnd - $timeStart) / 1000000000;
        Logger::outputDebugMessage('preparing visual tree needed: '.$timeDiff.'s');

        return $visualRoot;
    }
}