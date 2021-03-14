<?php
//schnickschnack-Idee: button for converting svg graphic into an image and download it
//todo handle optionally pkmn from previous generations (would include checking more learnsets per pkmn)
//todo optionally handle blacklists more or less stricter

class BackendHandler {
	//contain the data of the external wiki pages
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

	/**
	 * main function that creates and returns the breeding tree
	 */
	public function createBreedingTree () {
		//for performance measuring
		$timeStart = hrtime(true);

		$targetPkmnName = $this->targetPkmn;
		//contains the pkmn object from the external JSON data
		$targetPkmnData = $this->pkmnData->$targetPkmnName;

		//needed for preventing infinite recursion
		//a pkmn may only occur once in a branch, otherwise you would get an infinite loop
		$pkmnBlacklist = [$pkmn];
		$eggGroupBlacklist = [];
		$breedingTree = $this->createBreedingChainNode($targetPkmnData, $pkmnBlacklist, $eggGroupBlacklist);

		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		$this->out("backend needed ".$timeDiff." seconds");

		return $breedingTree;
	}

	/**
	 * recursive function that builds up the breeding chain nodes
	 * 
	 * depending on strictness pkmnBlacklist should use 
	 * pass by reference (stricter, faster, inaccurate) or 
	 * pass by value (looser, slower (in extreme cases more then 200 times slower), more accurate)
	 * 
	 * returns a BreedingChainNode objetc if pkmn can learn/inherit the move
	 * returns null if not
	 */
	private function createBreedingChainNode ($pkmn, &$pkmnBlacklist, $eggGroupBlacklist) {
		//todo form change moves (e. g. Rotom) should count as normal 
		//todo (check if there are breedable pkmn that have those form change learnsets)
		$chainNode = new BreedingChainNode($pkmn->name);

		if ($this->canLearnNormally($pkmn)) {
			//if a pkmn can learn the targeted move directly without breeding no possible successors are needed/wanted
			//this happens at the end of a tree branch
			return $chainNode;
		}

		if ($this->canInherit($pkmn)) {
			//calls createBreedingChainNode(...) for all suiting parents (i. e. not in any blacklist)
			//and adds them as a successor to chainNode if they can learn the move in some way
			$this->setPossibleParents($pkmn->eggGroup1, $pkmn->eggGroup2, $chainNode, $pkmnBlacklist, $eggGroupBlacklist);

			if (count($chainNode->getSuccessors()) > 0) {
				//todo this explanation is not the yellow from the egg
				//if a pkmn has no successors that can learn the targeted move (with the corresponding blacklists for the branch)
				//it hasn't anyone to inherit the move from --> branch doesn't get added to existing tree structure
				//because there is no 'successful' end
				return $chainNode;
			}
		}

		//todo the mass of "$eventLearnsets is undefined" debug messages sucks, find a way to avoid it
		if ($this->canLearnViaEvent($pkmn)) {
			//similar to the canLearnNormally section a few lines before this
			//event learnsets can however be hard or impossible to get so they only checked when there is no other way
			
			//marks that the chain node can only learn the move via event learnsets (needed for frontend) 
			$chainNode->setLearnsByEvent();
			return $chainNode;
		}

		return null;
	}

	/**
	 * calls setSuccessors for every eggGroup that's not been added to eggGroupBlacklist
	 * 
	 * pass by stuff for pkmnBlacklist is the same as in createBreedingChainNode
	 */
	private function setPossibleParents ($eggGroup1, $eggGroup2, $pkmnObj, &$pkmnBlacklist, $eggGroupBlacklist) {		
		if (!in_array($eggGroup1, $eggGroupBlacklist)) {
			$this->setSuccessors($eggGroup1, $pkmnObj, $eggGroup2, $pkmnBlacklist, $eggGroupBlacklist);
		}

		//some pkmn only have one egg group --> has to get checked via is_null()
		if (!is_null($eggGroup2) && !in_array($eggGroup2, $eggGroupBlacklist)) {
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
			/* if (!is_null($otherEggGroup)) {
				$newEggGroupBlacklist = array_merge($newEggGroupBlacklist, [$otherEggGroup]);
			} */

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

	private function canLearnViaEvent ($pkmn) {
		return $this->checkLearnsetType($pkmn->eventLearnsets);
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