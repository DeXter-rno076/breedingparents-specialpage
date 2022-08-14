<?php
require_once 'Track.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../tree_creation/PkmnTreeRoot.php';
require_once __DIR__.'/../tree_creation/BreedingSubtree.php';
require_once 'PostBreedingTreeCreationCheckpoint.php';
require_once __DIR__.'/../output_messages/ErrorMessage.php';

class BreedingTreeCreationTrack extends Track {
    public function passOn (): string {
        try {
            $breedingTreeRoot = $this->createBreedingSubTree();
            if (is_null($breedingTreeRoot)) {
                Logger::elog('critical error in creating breeding tree, aborting');
                return 'critical error in breeding tree creation';
            }
            $postBreedingTreeCreationCheckpoint = new PostBreedingTreeCreationCheckpoint($breedingTreeRoot);
            return $postBreedingTreeCreationCheckpoint->passOn();
        } catch (Exception $e) {
            $eMsg = ErrorMessage::constructWithError($e);
            $eMsg->output();
            return 'exception thrown in breeding tree creation';
        }
    }

    private function createBreedingSubTree (): ?BreedingSubtree {
        Logger::statusLog('CREATING BREEDING TREE NODES');
        $timeStart = hrtime(true);

        $breedingTreeRoot = new PkmnTreeRoot(Constants::$targetPkmnName);
        $breedingTreeRoot = $breedingTreeRoot->createBreedingSubtree([]);
        if (is_null($breedingTreeRoot)) {
            Logger::elog($breedingTreeRoot.'->createBreedingSubTree() returned null');
            return null;
        }

        $timeEnd = hrtime(true);
        $timeDiff = ($timeEnd - $timeStart) / 1000000000;
        Logger::outputDebugMessage('breeding tree creation needed: '.$timeDiff.'s');

        return $breedingTreeRoot;
    }
}