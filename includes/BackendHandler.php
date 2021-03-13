<?php
//schnickschnack-Idee: button for converting svg graphic into an image and download it
//todo handle optionally pkmn from previous generations (would include checking more learnsets per pkmn)

class BackendHandler {
	private $pkmnData = null;
	private $eggGroups = null;
	private $unbreedable = null;
	private $targetPkmn = '';
	private $targetMove = '';

	private $pageOutput = null;

	public function __construct ($pkmnData, $eggGroups, $unbreedable, $targetPkmn, $targetMove, $pageOutput) {
		$this->pkmnData = $pkmnData;
		$this->eggGroups = $eggGroups;
		$this->unbreedable = $unbreedable;
		$this->targetPkmn = $targetPkmn;
		$this->targetMove = $targetMove;
		$this->pageOutput = $pageOutput;
	}

	public function createBreedingTree () {
		$timeStart = hrtime(true);

		$pkmn = $this->targetPkmn;
		$targetPkmnData = $this->pkmnData->$pkmn;

		$pkmnBlacklist = [$pkmn];
		$breedingTree = $this->createBreedingChainNode($targetPkmnData, $pkmnBlacklist, [], 'root');

		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		$this->out("backend needed ".$timeDiff." seconds");

		return $breedingTree;
	}

	//todo split this up
	private function createBreedingChainNode ($pkmn, &$pkmnBlacklist, $eggGroupBlacklist) {
		//todo form change moves (e. g. Rotom) should count as normal 
		//todo (check if there are breedable pkmn that have those form change learnsets)

		if ($this->canLearnNormally($pkmn)) {
			$chainNode = new BreedingChainNode($pkmn->name);
			return $chainNode;
		}

		if ($this->canInherit($pkmn)) {
			$chainNode = new BreedingChainNode($pkmn->name);

			$this->setPossibleParents($pkmn->eggGroup1, $pkmn->eggGroup2, $chainNode, $pkmnBlacklist, $eggGroupBlacklist);

			if (count($chainNode->getSuccessors()) > 0) {
				return $chainNode;
			}
		}

		//todo the mass of "$eventLearnsets is undefined" debug messages sucks, find a way to avoid it
		if ($this->checkLearnsetType($pkmn->eventLearnsets)) {
			$chainNode = new BreedingChainNode($pkmn->name);
			$chainNode->setLearnsByEvent();
			return $chainNode;
		}

		return null;
	}

	private function setPossibleParents ($eggGroup1, $eggGroup2, $pkmnObj, &$pkmnBlacklist, $eggGroupBlacklist) {		
		if (!in_array($eggGroup1, $eggGroupBlacklist, true)) {
			$this->setSuccessors($eggGroup1, $pkmnObj, $eggGroup2, $pkmnBlacklist, $eggGroupBlacklist);
		}

		if (!is_null($eggGroup2) && !in_array($eggGroup2, $eggGroupBlacklist, true)) {
			$this->setSuccessors($eggGroup2, $pkmnObj, $eggGroup1, $pkmnBlacklist, $eggGroupBlacklist);
		}
	}

	private function setSuccessors ($eggGroup, $pkmnObj, $otherEggGroup, &$pkmnBlacklist, $eggGroupBlacklist) {
		$eggGroupData = $this->eggGroups->$eggGroup;

		foreach ($eggGroupData as $pkmnName) {
			$pkmnData = $this->pkmnData->$pkmnName;

			if (is_null($pkmnData)) {
				//todo this can be removed, when the pkmn data program removes pkmn that are not in the handled gen
				continue;
			}
			if (in_array($pkmnName, $pkmnBlacklist)) {
				continue;
			}

			//* this handling of the blacklists is more inaccurate but creates less large amounts of results
			$newEggGroupBlacklist = array_merge($eggGroupBlacklist, [$eggGroup]);
			if (!is_null($otherEggGroup)) {
				$newEggGroupBlacklist = array_merge($newEggGroupBlacklist, [$otherEggGroup]);
			}

			$pkmnBlacklist[] = $pkmnName;

			$successor = $this->createBreedingChainNode($pkmnData, $pkmnBlacklist, $newEggGroupBlacklist);
			if ($successor !== null) {
				$pkmnObj->addSuccessor($successor);
			}
		}
	}

	//==================================================================
	//learnability checks
	
	private function canLearnNormally ($pkmn) {
		$levelLearnability = $this->checkLearnsetType($pkmn->levelLearnsets);
		if ($levelLearnability) {
			return true;
		}

		$tmtrLearnability = $this->checkLearnsetType($pkmn->tmtrLearnsets);
		if ($tmtrLearnability) {
			return true;
		}

		$tutorLearnability = $this->checkLearnsetType($pkmn->tutorLearnsets);
		if ($tutorLearnability) {
			return true;
		}

		return false;
	}

	private function canInherit ($pkmn) {
		return $this->checkLearnsetType($pkmn->breedingLearnsets);
	}

	private function checkLearnsetType ($learnset) {
		//this method is called for a couple learnset types without checking if the pkmn has these learnsets
		if (is_null($learnset)) {
			return false;
		}

		foreach ($learnset as $item) {
			if ($item === $this->targetMove) {
				return true;
			}
		}

		return false;
	}

	//==================================================================
	//output stuff for debugging

	private function out ($msg) {
		$this->pageOutput->addHTML($msg."<br />");
	}
}

class BreedingChainNode {
    //todo change access rights after implementing frontend
    public $name;
    public $successors = [];
    public $treeSectionHeight;
    public $treeYOffset;
    private $learnsByEvent = false;
    
    public function __construct ($name) {
        $this->name = $name;
    }

    public function addSuccessor ($successor) {
        array_push($this->successors, $successor);
    }

    public function getSuccessors () {
        return $this->successors;
    }

    public function setLearnsByEvent () {
        $this->learnsByEvent = true;
    }

    public function getLearnsByEvent () {
        return $this->learnsByEvent;
    }
}
?>