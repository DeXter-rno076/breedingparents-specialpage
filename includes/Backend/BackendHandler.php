<?php
require 'BreedingChainNode.php';
abstract class BackendHandler {
	//contain the data of the external wiki pages
	protected $pkmnData = null;
	protected $eggGroups = null;
	protected $unbreedable = null;

	protected $targetPkmn = '';
	protected $targetMove = '';

	protected $pageOutput = null;

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
		$pkmnBlacklist = [$targetPkmnData];
		$eggGroupBlacklist = [];
		$breedingTree = $this->createBreedingChainNode($targetPkmnData, $pkmnBlacklist, $eggGroupBlacklist);

		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		$this->out("backend needed ".$timeDiff." seconds");

		return $breedingTree;
	}

	/**
	 * calls setSuccessors for every eggGroup that's not been added to eggGroupBlacklist
	 * 
	 * depending on strictness pkmnBlacklist should use 
	 * pass by reference (stricter, faster, inaccurate) or 
	 * pass by value (looser, slower (in extreme cases more then 200 times slower), more accurate)
	 */
	protected function setPossibleParents ($eggGroup1, $eggGroup2, $pkmnObj, &$pkmnBlacklist, $eggGroupBlacklist) {		
		if (!in_array($eggGroup1, $eggGroupBlacklist)) {
			$this->setSuccessors($eggGroup1, $pkmnObj, $eggGroup2, $pkmnBlacklist, $eggGroupBlacklist);
		}

		//some pkmn only have one egg group --> has to get checked via is_null()
		if (!is_null($eggGroup2) && !in_array($eggGroup2, $eggGroupBlacklist)) {
			$this->setSuccessors($eggGroup2, $pkmnObj, $eggGroup1, $pkmnBlacklist, $eggGroupBlacklist);
		}
	}

	/**
	 * calls createBreedingChainNode() for every successor that is not in pkmnBlacklist
	 * adds eggGroup(s) and the successor to blackLists
	 * if the successor can learn the move it is added to pkmnObj's successors
	 */
	protected function setSuccessors ($eggGroup, $pkmnObj, $otherEggGroup, &$pkmnBlacklist, $eggGroupBlacklist) {
		$eggGroupData = $this->eggGroups->$eggGroup;

		foreach ($eggGroupData as $potSuccessorName) {
			$potSuccessorData = $this->pkmnData->$potSuccessorName;

			if (is_null($potSuccessorData)) {
				//todo this can be removed, when the pkmn data program removes pkmn that are not in the handled gen
				continue;
			}
			if (in_array($potSuccessorName, $pkmnBlacklist)) {
				continue;
			}

			//* this handling of the blacklists is more inaccurate but creates less large amounts of results
			$newEggGroupBlacklist = array_merge($eggGroupBlacklist, [$eggGroup]);
			/* if (!is_null($otherEggGroup)) {
				$newEggGroupBlacklist = array_merge($newEggGroupBlacklist, [$otherEggGroup]);
			} */

			$pkmnBlacklist[] = $potSuccessorName;

			$successor = $this->createBreedingChainNode($potSuccessorData, $pkmnBlacklist, $newEggGroupBlacklist);
			if ($successor !== null) {
				//this is called when successor is able to learn targetMove
				$pkmnObj->addSuccessor($successor);
			}
		}
	}

	//==================================================================
	//learnability checks
	
	/**
	 * checks whether the pkmn can learn targetMove via level, tmtr or tutor
	 */
	protected function canLearnNormally ($pkmn) {
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

	protected function canInherit ($pkmn) {
		//not necessarily needed but it prevents masses of debug logs
		if (!isset($pkmn->breedingLearnsets)) return false;		

		return $this->checkLearnsetType($pkmn->breedingLearnsets);
	}

	protected function canLearnViaEvent ($pkmn) {
		//not necessarily needed but it prevents masses of debug logs
		if (!isset($pkmn->eventLearnsets)) return false;

		return $this->checkLearnsetType($pkmn->eventLearnsets);
	}

	protected function checkLearnsetType ($learnset) {
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

	protected function out ($msg) {
		$this->pageOutput->addHTML($msg."<br />");
	}
}
?>