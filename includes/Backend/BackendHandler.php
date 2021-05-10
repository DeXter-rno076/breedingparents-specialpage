<?php
require_once 'BreedingChainNode.php';
//for some reason prepending __DIR__ is a fix for require not liking relative paths
require_once __DIR__.'/../Constants.php';
require_once 'SuccessorFilter.php';

abstract class BackendHandler {
	//paremeter structure: pkmnObj, ..., eggGroup, otherEggGroup, eggGroupBlacklist, pkmnBlacklist

	protected $pageOutput = null;//temp

	public function __construct (OutputPage $pageOutput) {
		$this->pageOutput = $pageOutput;
	}

	/**
	 * main function that creates and returns the breeding tree
	 */
	public function createBreedingTree () : BreedingChainNode {
		//for performance measuring
		$timeStart = hrtime(true);

		$targetPkmnName = Constants::$targetPkmn;
		//contains the pkmn object from the external JSON data
		$targetPkmnData = Constants::$pkmnData->$targetPkmnName;

		//needed for preventing infinite recursion
		//a pkmn may only occur once in a branch,
		//		otherwise you would get an infinite loop
		$pkmnBlacklist = [$targetPkmnData->name];
		$eggGroupBlacklist = [];

		$breedingTree = $this->createBreedingChainNode(
			$targetPkmnData,
			$eggGroupBlacklist,
			$pkmnBlacklist
		);

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
	 * pass by value (looser, 
	 * 		slower (in extreme cases more then 200 times slower), more accurate)
	 */
	protected function setPossibleParents (
		BreedingChainNode $node,
		String $eggGroup1,
		?String $eggGroup2,
		Array $eggGroupBlacklist,
		Array &$pkmnBlacklist
	) {		
		if (!in_array($eggGroup1, $eggGroupBlacklist)) {
			$this->setSuccessors(
				$node,
				$eggGroup1,
				$eggGroup2,
				$eggGroupBlacklist,
				$pkmnBlacklist
			);
		}

		//some pkmn only have one egg group --> has to get checked via is_null()
		if (!is_null($eggGroup2)) { 
			if(!in_array($eggGroup2, $eggGroupBlacklist)) {
				$this->setSuccessors(
					$node,
					$eggGroup2,
					$eggGroup1,
					$eggGroupBlacklist,
					$pkmnBlacklist
				);
			}
		}
	}

	/**
	 * calls createBreedingChainNode() for every successor that is not in pkmnBlacklist
	 * adds eggGroup(s) and the successor to blackLists
	 * if the successor can learn the move it is added to pkmnObj's successors
	 */
	private function setSuccessors (
		BreedingChainNode $node,
		String $eggGroup,
		//needed for stronger blacklist handling (not implemented now)
		?String $otherEggGroup,
		Array $eggGroupBlacklist,
		Array &$pkmnBlacklist
	) {
		$eggGroupPkmnList = Constants::$eggGroups->$eggGroup;
		$filter = new SuccessorFilter($node, $pkmnBlacklist, $eggGroupPkmnList);
		$filteredList = $filter->filter();

		foreach ($filteredList as $potSuccessorName) {
			$potSuccessorData = Constants::$pkmnData->$potSuccessorName;

			$newEggGroupBlacklist = $this->createNewEggGroupBlacklist(
				$eggGroup,
				$otherEggGroup,
				$eggGroupBlacklist
			);

			$this->addSuccessor(
				$node,
				$potSuccessorData,
				$newEggGroupBlacklist,
				$pkmnBlacklist
			);
		}
	}

	private function createNewEggGroupBlacklist (
		String $eggGroup,
		?String $otherEggGroup,
		Array $eggGroupBlacklist
	) : Array {
		/* if (!is_null($otherEggGroup)) {
			$newEggGroupBlacklist = array_merge(
				$newEggGroupBlacklist,
				[$otherEggGroup]
			);
		} */
		//* this handling of the blacklists is more inaccurate
		//*		 but creates less large amounts of results
		$newEggGroupBlacklist = array_merge(
			$eggGroupBlacklist,
			[$eggGroup]
		);
		return $newEggGroupBlacklist;
	}

	//todo name is meh (addSuccessor is already used in BreedingChainNode)
	private function addSuccessor (
		BreedingChainNode $node,
		StdClass $potSuccessorData,
		Array $eggGroupBlacklist,
		Array &$pkmnBlacklist
	) {
		$pkmnBlacklist[] = $potSuccessorData->name;

		$successor = $this->createBreedingChainNode(
			$potSuccessorData,
			$eggGroupBlacklist,
			$pkmnBlacklist
		);

		if ($successor !== null) {
			//this is called when successor is able to learn targetMove
			//TODO does this work or do I have to use some pass by reference stuff?
			$node->addSuccessor($successor);
		}
	}

	//===================================================================================
	//learnability checks

	/**
	 * checks whether the pkmn can learn targetMove via level, tmtr or tutor
	 */
	protected function canLearnNormally (StdClass $pkmnObj) : bool {
		$levelLearnability = $this->checkLearnsetType($pkmnObj->levelLearnsets);
		if ($levelLearnability) {
			return true;
		}

		$tmtrLearnability = false;
		if (isset($pkmnObj->tmtrLearnsets)) {
			//prevent a couple of debugging messages
			$tmtrLearnability = $this->checkLearnsetType($pkmnObj->tmtrLearnsets);
		}
		if ($tmtrLearnability) {
			return true;
		}

		$tutorLearnability = $this->checkLearnsetType($pkmnObj->tutorLearnsets);
		if ($tutorLearnability) {
			return true;
		}

		return false;
	}

	protected function canInherit (StdClass $pkmnObj) : bool {
		//not necessarily needed but it prevents masses of debug logs
		if (!isset($pkmnObj->breedingLearnsets)) {
			return false;
		}		

		return $this->checkLearnsetType($pkmnObj->breedingLearnsets);
	}

	protected function canLearnViaEvent (StdClass $pkmnObj) : bool {
		//not necessarily needed but it prevents masses of debug logs
		if (!isset($pkmnObj->eventLearnsets)) {
			return false;
		}

		return $this->checkLearnsetType($pkmnObj->eventLearnsets);
	}

	protected function checkLearnsetType (?Array $learnset) : bool {
		if (is_null($learnset)) {
			return false;
		}

		foreach ($learnset as $move) {
			if ($move === Constants::$targetMove) {
				return true;
			}
		}

		return false;
	}

	//==================================================================
	//output stuff for debugging

	protected function out (String $msg) {
		$this->pageOutput->addHTML($msg."<br />");
	}
}