<?php
require_once __DIR__.'/../Pkmn.php';
require_once 'PkmnData.php';
require_once __DIR__.'/../Logger.php';
require_once 'SuccessorFilter.php';

class BreedingTreeNode extends Pkmn {
    private bool $isRoot = false;
    private Array $successors = [];
    private PkmnData $data;
    private bool $learnsByEvent = false;
    private bool $learnsByOldGen = false;

    public function __construct (string $pkmnName, bool $isRoot = false) {
        $this->data = new PkmnData($pkmnName);
        parent::__construct($this->data->getName(), $this->data->getID());
        $this->isRoot = $isRoot;
    }

    public function createBreedingTreeNode (
            Array $eggGroupBlacklist): ?BreedingTreeNode {
        Logger::statusLog('creating tree node of '
            .$this.' with eggGroupBlacklist: '.json_encode($eggGroupBlacklist));
        /*in this single spot parameter $eggGroupBlacklist MUST NOT be
        pass by reference (this would create wrong breeding trees)*/
        if ($this->canLearnDirectly()) {
            Logger::statusLog($this.' can learn the move directly');
            return $this;
        }

        if ($this->canLearnByOldGen()) {
            Logger::statusLog($this.' could learn the move in an old gen');
            /*learning by old gen might be easier to get than breeding but also maybe not.
            It depends on the user, therefore learnsByOldGen is checked and marked before
            inheritence check, but it only exits this method if the pkmn can't inherit the move
            (learning by old gen is in most cases easier than learning by event)*/
            $this->setLearnsByOldGen();
        }

        if ($this->canInherit()) {
            Logger::statusLog($this.' could inherit the move');
            $this->selectSuccessors($eggGroupBlacklist);

            if ($this->hasSuccessors()) {
                Logger::statusLog($this.' can inherit the move, successors found');
                return $this;
            }
        } else if ($this->isRoot) {
            $lowestEvolution = $this->data->getLowestEvolution();
            if ($lowestEvolution !== $this->name) {
                $lowestEvoInstance = new BreedingTreeNode($lowestEvolution);
                $lowestEvoNode = $lowestEvoInstance->createBreedingTreeNode($eggGroupBlacklist);
                if (!is_null($lowestEvoNode)) {
                    //not (this->oldGen and (evo->oldGen or evo->event))
                    if (!$this->getLearnsByOldGen() ||
                        !$lowestEvoNode->getLearnsByOldGen() ||
                        !$lowestEvoNode->getLearnsByEvent()
                    ) {
                        $this->addSuccessor($lowestEvoNode);
                        return $this;
                    }
                }
            }
        }

        if ($this->getLearnsByOldGen()) {
            return $this;
        }

        if ($this->canLearnByEvent()) {
            Logger::statusLog($this.' can learn the move by event');
            $this->setLearnsByEvent();
            return $this;
        }

        Logger::statusLog($this.' can\'t learn the move');
        return null;
    }

    //todo maybe separate isRoot section
    private function selectSuccessors (Array &$eggGroupBlacklist) {
        $eggGroup1 = $this->data->getEggGroup1();
        $eggGroup2 = $this->data->getEggGroup2();

        if ($this->isRoot) {
            /*root pkmn is at the start => it is the only pkmn that
            doesnt need an egg group for a connection to a predecessor
            because it doesn't have one*/
            $eggGroupBlacklist[] = $eggGroup1;

            if ($this->data->hasSecondEggGroup()) {
                /*egg group 2 has to be called first because
                both method calls need both egg groups in the blacklist*/
                $eggGroupBlacklist[] = $eggGroup2;

                $this->createSuccessorsTreeSection(
                    $eggGroupBlacklist, $eggGroup2);
            }
            $this->createSuccessorsTreeSection($eggGroupBlacklist, $eggGroup1);

        }

        if (!$this->data->hasSecondEggGroup()) {
            /*One egg group is ALWAYS in the blacklist (except for
            root node pkmn).
            If the pkmn has only one egg group it MUST learn the move directly
            in order to be a suitable parent.
            To be able to be in the middle of a breeding chain a pkmn
            needs two egg groups (one for successor(s), one for predecessor).
            A pkmn with one egg group can ONLY be the end of a chain
            (=> must learn the move directly).*/
            return;
        }

        $otherEggGroup = $this->getOtherEggGroup($eggGroupBlacklist);
        if ($otherEggGroup === '') {
            Logger::wlog('selectSuccessors call on '.$this.', somehow '
                .'getOtherEggGroup returned null, even though this case '
                .'should have already been covered by an if block');
            return;
        }

        $eggGroupBlacklist[] = $otherEggGroup;
        $this->createSuccessorsTreeSection($eggGroupBlacklist, $otherEggGroup);
    }

    private function createSuccessorsTreeSection (
            Array &$eggGroupBlacklist, string $whitelisted) {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .' eggGroupBlacklist: '.json_encode($eggGroupBlacklist)
            .', whitelisted: '.$whitelisted);
        $eggGroupPkmnList = Constants::$eggGroups->$whitelisted;
        $filter = new SuccessorFilter(
            $eggGroupBlacklist, $whitelisted, $eggGroupPkmnList);
        $potSuccessorList = $filter->filter();

        foreach ($potSuccessorList as $potSuccessor) {
            $potSuccessorInstance = null;
            try {
                $potSuccessorInstance = new BreedingTreeNode($potSuccessor);
            } catch (AttributeNotFoundException $e) {
                Logger::elog('couldn\'t create breeding tree node, error: '.$e);
                continue;
            }
            $potSuccessorNode = $potSuccessorInstance->createBreedingTreeNode($eggGroupBlacklist);

            if (!is_null($potSuccessorNode)) {
                $this->addSuccessor($potSuccessorNode);
            }
        }
    }

    private function getOtherEggGroup (Array &$eggGroupBlacklist): string {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .' eggGroupBlacklist: '.json_encode($eggGroupBlacklist));
        /*One egg group is always in the blacklist because of
        the connection to the predecessor.
        Possible successors can only come from the other one.*/
        $eggGroup1 = $this->data->getEggGroup1();
        $eggGroup2 = $this->data->getEggGroup2();
        $otherEggGroup = '';
        if (!in_array($eggGroup1, $eggGroupBlacklist)) {
            Logger::statusLog('egg group 1 '.$eggGroup1.' found for '.$this);
            $otherEggGroup = $eggGroup1;
        } else if (!in_array($eggGroup2, $eggGroupBlacklist)) {
            Logger::statusLog('egg group 2 '.$eggGroup2.' found for '.$this);
            $otherEggGroup = $eggGroup2;
        } else {
            //all egg groups blocked
            Logger::statusLog('all egg groups of '.$this. ' blocked');
            return '';
        }

        Logger::statusLog('returning '.$otherEggGroup);
        return $otherEggGroup;
    }

    private function canLearnDirectly (): bool {
        $directLearnsets = $this->data->getDirectLearnsets();
        return $this->checkLearnsetType($directLearnsets, 'directly');
    }

    private function canInherit (): bool {
        $unbreedable = $this->data->getUnbreedable();
        if ($unbreedable) {
            Logger::statusLog($this.' is unbreedable');
            return false;
        }

        $breedingLearnsets = $this->data->getBreedingLearnsets();
        return $this->checkLearnsetType($breedingLearnsets, 'breeding');
    }

    private function canLearnByEvent (): bool {
        $eventLearnsets = $this->data->getEventLearnsets();
        return $this->checkLearnsetType($eventLearnsets, 'event');
    }

    private function canLearnByOldGen (): bool {
        $oldGenLearnsets = $this->data->getOldGenLearnsets();
        return $this->checkLearnsetType($oldGenLearnsets, 'old gen');
    }

    private function checkLearnsetType (Array $learnsetList, string $learnsetType): bool {
        foreach ($learnsetList as $move) {
            if ($move === Constants::$targetMove) {
                Logger::statusLog('found target move in '.$learnsetType.' learnset');
                return true;
            }
        }
        return false;
    }

    public function hasSuccessors (): bool {
        $successorCount = count($this->successors);
        $hasSuccessors = $successorCount > 0;
        return $hasSuccessors;
    }

    public function getSuccessors (): Array {
        return $this->successors;
    }

    private function addSuccessor (BreedingTreeNode $newSuccessor) {
        array_push($this->successors, $newSuccessor);
    }

    private function setLearnsByEvent () {
        $this->learnsByEvent = true;
    }

    public function getLearnsByEvent (): bool {
        return $this->learnsByEvent;
    }

    private function setLearnsByOldGen () {
        $this->learnsByOldGen = true;
    }

    public function getLearnsByOldGen () {
        return $this->learnsByOldGen;
    }

    public function getLogInfo (): string {
        $msg = 'BreedingTreeNode:\'\'\''.$this->data->getName().'\'\'\';count='.count($this->successors);
        if (isset($this->learnsByEvent) && $this->learnsByEvent) {
            $msg .= ';learnsByEvent';
        }
        if (isset($this->learnsByOldGen) && $this->learnsByOldGen) {
            $msg .= ';learnsByOldGen';
        }
        if ($this->isRoot) {
            $msg .= ';isRoot';
        }
        $msg .= ';;';

        return $msg;
    }

    public function __toString (): string {
        return $this->getLogInfo();
    }
}