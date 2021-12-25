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
        Logger::statusLog('creating BreedingTreeNode instance for '
            .$pkmnName.', isRoot='.$isRoot);
        $this->data = new PkmnData($pkmnName);
        parent::__construct($this->data->getName(), $this->data->getID());
        $this->isRoot = $isRoot;
    }

    public function createBreedingTreeNode (
            Array $eggGroupBlacklist): ?BreedingTreeNode {
        //todo add checking learns by old gen
        Logger::statusLog('creating tree node of '
            .$this.' with eggGroupBlacklist: '.json_encode($eggGroupBlacklist));
        /*in this single spot parameter $eggGroupBlacklist MUST NOT be
        pass by reference (this would create wrong breeding trees)*/
        if ($this->canLearnDirectly()) {
            Logger::statusLog($this.' can learn the move directly');
            return $this;
        }

        if ($this->canInherit()) {
            Logger::statusLog($this.' could inherit the move');
            $this->selectSuccessors($eggGroupBlacklist);

            if ($this->hasSuccessors()) {
                Logger::statusLog($this.' can inherit the move, successors found');
                return $this;
            }
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
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .' with parameter eggGroupBlacklist: '
            .json_encode($eggGroupBlacklist));
        $eggGroup1 = $this->data->getEggGroup1();
        $eggGroup2 = $this->data->getEggGroup2();
        Logger::statusLog($this. 'has egg groups '.$eggGroup1.' and '.$eggGroup2);

		if ($this->isRoot) {
            Logger::statusLog($this.' is root => adding both egg groups');
			/*root pkmn is at the start => it is the only pkmn that 
            doesnt need an egg group for a connection to a predecessor
            because it doesn't have one*/
            $eggGroupBlacklist[] = $eggGroup1;

			if ($eggGroup2 !== null) {
                /*egg group 2 has to be called first because
                both method calls need both egg groups in the blacklist*/
				$eggGroupBlacklist[] = $eggGroup2;

				$this->createSuccessorsTreeSection(
                    $eggGroupBlacklist, $eggGroup2);
			}
			$this->createSuccessorsTreeSection($eggGroupBlacklist, $eggGroup1);

		}

        if ($eggGroup2 === null) {
            Logger::statusLog($this.' has no second egg group => can\'t be a middle node');
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
        if ($otherEggGroup === null) {
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

    private function getOtherEggGroup (Array &$eggGroupBlacklist): ?string {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .' eggGroupBlacklist: '.json_encode($eggGroupBlacklist));
        /*One egg group is always in the blacklist because of
        the connection to the predecessor.
		Possible successors can only come from the other one.*/
        $eggGroup1 = $this->data->getEggGroup1();
        $eggGroup2 = $this->data->getEggGroup2();
		$otherEggGroup = NULL;
		if (!in_array($eggGroup1, $eggGroupBlacklist)) {
            Logger::statusLog('egg group 1 '.$eggGroup1.' found for '.$this);
			$otherEggGroup = $eggGroup1;
		} else if (!in_array($eggGroup2, $eggGroupBlacklist)) {
            Logger::statusLog('egg group 2 '.$eggGroup2.' found for '.$this);
			$otherEggGroup = $eggGroup2;
		} else {
			//all egg groups blocked
            Logger::statusLog('all egg groups of '.$this. ' blocked');
			return null;
		}

        Logger::statusLog('returning '.$otherEggGroup);
        return $otherEggGroup;
    }

    private function canLearnDirectly (): bool {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        $directLearnsets = $this->data->getDirectLearnsets();
        if ($directLearnsets === null) {
            Logger::statusLog(
                $this.' has no direct learnsets, returning false');
			return false;
		}

		return $this->checkLearnsetType($directLearnsets);
    }

    private function canInherit (): bool {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        $unbreedable = $this->data->getUnbreedable();
        if (is_null($unbreedable)) {
            Logger::elog('pkmn data of '.$this->data->name.' has no'
                .' unbreedable property');
            return false;
        }
		if ($unbreedable) {
            Logger::statusLog($this.' is unbreedable, returning false');
			return false;
		}
		
        $breedingLearnsets = $this->data->getBreedingLearnsets();
		if ($breedingLearnsets === null) {
            Logger::statusLog($this.' has no breeding learnsets, returning false');
			return false;
		}

		return $this->checkLearnsetType($breedingLearnsets);
    }

    private function canLearnByEvent (): bool {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        $eventLearnsets = $this->data->getEventLearnsets();
		if ($eventLearnsets === null) {
            Logger::statusLog($this.' has no event learnsets, returning false');
			return false;
		}

		return $this->checkLearnsetType($eventLearnsets);
    }

    private function checkLearnsetType (Array $learnsetList): bool {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .', learnsetList: '.json_encode($learnsetList));
        foreach ($learnsetList as $move) {
			if ($move === Constants::$targetMove) {
				Logger::statusLog('found target move in learnset, returning true');
                return true;
			}
		}

        Logger::statusLog('couldn\'t find target move in learnset'
            .', returning false');
		return false;
    }

    public function hasSuccessors (): bool {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        $successorCount = count($this->successors);
        $hasSuccessors = $successorCount > 0;
        Logger::statusLog($this.' has '.$successorCount.' successors'
            .' returning '.$hasSuccessors);
        return $hasSuccessors;
    }

    public function getSuccessors (): Array {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        return $this->successors;
    }

    private function addSuccessor (BreedingTreeNode $newSuccessor) {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .', newSuccessor is '.$newSuccessor);
        Logger::statusLog('successor list before: '.json_encode($this->successors));
        array_push($this->successors, $newSuccessor);
        Logger::statusLog('successor list after: '.json_encode($this->successors));
    }

    private function setLearnsByEvent () {
        Logger::statusLog('calling '.__FUNCTION__.' on '
            .$this.', setting learnsbyEvent to true');
        $this->learnsByEvent = true;
    }

    public function getLearnsByEvent (): bool {
        Logger::statusLog('calling '.__FUNCTION__.' on '
            .$this.', returning '.$this->learnsByEvent);
        return $this->learnsByEvent;
    }

    private function setLearnsByOldGen () {
        Logger::statusLog('calling '.__FUNCTION__.' on '
            .$this.', setting learnsByOldGen to true');
        $this->learnsByOldGen = true;
    }

    public function getLearnsByOldGen () {
        Logger::statusLog('calling '.__FUNCTION__.' on '
            .$this.', returning '.$this->learnsByOldGen);
        return $this->learnsByOldGen;
    }

    public function getLogInfo (): string {
        $msg = 'BreedingTreeNode:'.$this->data->getName().';count='.count($this->successors);
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