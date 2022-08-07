<?php
require_once 'Track.php';
require_once __DIR__.'/../tree_creation/BreedingRootSubtree.php';
require_once __DIR__.'/../svg_creation/VisualSubtree.php';
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

    private function createFrontendRoot (): VisualSubtree {
        Logger::statusLog('CREATING VISUALNODE INSTANCES');
        $timeStart = hrtime(true);

        $visualRoot = new VisualSubtree($this->breedingTreeRoot);
        $visualRoot->prep();

        $timeEnd = hrtime(true);
        $timeDiff = ($timeEnd - $timeStart) / 1000000000;
        Logger::outputDebugMessage('creating frontend pkmn tree needed: '.$timeDiff.'s');

        return $visualRoot;
    }
}