<?php
require_once 'Track.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../tree_creation/PkmnTreeRoot.php';
require_once __DIR__.'/../tree_creation/BreedingSubtree.php';
require_once 'PostBreedingTreeCreationCheckpoint.php';

class BreedingTreeCreationTrack extends Track {
    public function passOn (): string {
        $breedingTreeRoot = $this->createBreedingSubTree();
        $postBreedingTreeCreationCheckpoint = new PostBreedingTreeCreationCheckpoint($breedingTreeRoot);
        return $postBreedingTreeCreationCheckpoint->passOn();
    }

    private function createBreedingSubTree (): BreedingSubtree {
        Logger::statusLog('CREATING BREEDING TREE NODES');
        $timeStart = hrtime(true);

        $breedingTreeRoot = new PkmnTreeRoot(Constants::$targetPkmnName);
        $breedingTreeRoot = $breedingTreeRoot->createBreedingSubtree([]);

        $timeEnd = hrtime(true);
        $timeDiff = ($timeEnd - $timeStart) / 1000000000;
        Logger::outputDebugMessage('breeding tree creation needed: '.$timeDiff.'s');

        return $breedingTreeRoot;
    }
}