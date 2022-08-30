<?php
require_once 'BreedingTreeNode.php';
require_once 'PkmnTreeNode.php';
require_once 'BreedingRootSubtree.php';
require_once 'SuccessorMixer.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';
require_once __DIR__.'/../output_messages/ErrorMessage.php';

class PkmnTreeRoot extends PkmnTreeNode {
    public function __construct (string $pkmnName) {
        parent::__construct($pkmnName);
    }

    /**
     * @param array $eggGroupBlacklist - is ignored because tree roots always start a new blacklist
     */
    public function createBreedingSubtree (array $eggGroupBlacklist) {
        Logger::statusLog('creating tree root node of '.$this);
        $rootSubtree = new BreedingRootSubtree($this, []);
        if ($this->data->canLearnDirectly()) {
            Logger::statusLog($this.' can learn the move directly');
            $this->learnabilityStatus->setLearnsDirectly();
        }

        if ($this->data->canLearnByOldGen()) {
            Logger::statusLog($this.' could learn the move in an old gen');
            /*learning by old gen might be easier to get than breeding but also maybe not.
            It depends on the user, therefore learnsByOldGen is checked and marked before
            inheritence check, but it only exits this method if the pkmn can't inherit the move
            (learning by old gen is in most cases easier than learning by event)*/
            $this->learnabilityStatus->setLearnsByOldGen();
        }

        if ($this->data->canLearnByBreeding()) {
            Logger::statusLog($this.' could inherit the move');
            $this->learnabilityStatus->setCouldLearnByBreeding();
            $emptyEggGroupBlacklist = [];
            $successors = $this->selectSuccessors($emptyEggGroupBlacklist);
            $rootSubtree->addSuccessors($successors);

            if ($rootSubtree->hasSuccessors()) {
                $this->learnabilityStatus->setLearnsByBreeding();
                Logger::statusLog($this.' can inherit the move, successors found');
            }
        } else {
            $evoSuccessor = $this->tryBreedingChainOverLowestEvolution();
            if (!is_null($evoSuccessor)) {
                $this->learnabilityStatus->setLearnsFromEvo();
                $rootSubtree->addSuccessor($evoSuccessor);
            }
        }

        if ($this->data->canLearnByEvent()) {
            Logger::statusLog($this.' can learn the move by event');
            $this->learnabilityStatus->setLearnsByEvent();
        }

        return $rootSubtree;
    }

    protected function selectSuccessors (array &$eggGroupBlacklist): array {
        $successors = $this->selectSuccessorsOfRootNode();

        $mixer = new SuccessorMixer($this);
        $successors = $mixer->mix($successors);

        return $successors;
    }

    private function selectSuccessorsOfRootNode (): array {
        $eggGroup1 = $this->data->getEggGroup1();

        $firstSubTreeBlacklist = [];
        $secondSubTreeBlacklist = [$eggGroup1];

        if ($this->data->hasSecondEggGroup()) {
            $firstSubTreeBlacklist[] = $this->data->getEggGroup2();
        }

        $successors = $this->createSuccessorsTreeSection($firstSubTreeBlacklist, $eggGroup1, $eggGroup1);
        if ($this->data->hasSecondEggGroup()) {
            $eggGroup2 = $this->data->getEggGroup2();

            $successors = array_merge($successors, $this->createSuccessorsTreeSection(
                $secondSubTreeBlacklist, $eggGroup2));
        }
        return $successors;
    }

    /**
     * If the tree root is an evolution it can't inherit any moves (because it can't be breeded directly).
     * So inheritance is tried on its lowest evo.
     */
    private function tryBreedingChainOverLowestEvolution (): ?BreedingRootSubtree {
        if ($this->data->isLowestEvolution()) {
            return null;
        }
        Logger::statusLog($this.' is not the lowest evolution in it\'s line, trying an evo connection');

        $lowestEvoInstance = null;
        try {
            $lowestEvoName = $this->data->getLowestEvo();
            $lowestEvoInstance = new PkmnTreeRoot($lowestEvoName);
        } catch (AttributeNotFoundException $e) {
            $errorMessage = ErrorMessage::constructWithError($e);
            $errorMessage->output();
            return null;
        }

        $lowestEvoNodeSubTree = $lowestEvoInstance->createBreedingSubtree([]);
        if (is_null($lowestEvoNodeSubTree)) {
            Logger::elog($lowestEvoInstance.'->createBreedingSubtree() returned null');
            return null;
        }
        $lowestEvoNode = $lowestEvoNodeSubTree->getRoot();
        $evoLearnability = $lowestEvoNode->getLearnabilityStatus();

        if ($evoLearnability->getCouldLearnByBreeding()) {
            $this->learnabilityStatus->setCouldLearnByBreeding();
        }
        if ($evoLearnability->canLearn()) {
            if ($this->breedingChainOverLowestEvolutionHasAnAdvantage($lowestEvoNodeSubTree)) {
                return $lowestEvoNodeSubTree;
            }
        }
        return null;
    }

    private function breedingChainOverLowestEvolutionHasAnAdvantage (BreedingRootSubtree $lowestEvoSubTree) {
        if ($lowestEvoSubTree->hasSuccessors()) {
            return true;
        }
        $lowestEvoNode = $lowestEvoSubTree->getRoot();
        /*NOT ( this learns by old gen AND evo learns by old gen or event)
        without negation at the beginning:
        -> this does not learn via old gen or evo learns neither by old gen nor event*/
        return ! ($this->learnabilityStatus->getLearnsByOldGen()
            && ($lowestEvoNode->learnabilityStatus->getLearnsByOldGen()
                || $lowestEvoNode->learnabilityStatus->getLearnsByEvent()));
    }

    public function getLogInfo (): string {
        $normalPkmnNodeLogInfo = parent::getLogInfo();
        return str_replace(';;', 'root;;', $normalPkmnNodeLogInfo);
    }
}