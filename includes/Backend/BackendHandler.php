<?php
//for some reason prepending __DIR__ is a fix for require not liking relative paths
require_once __DIR__.'/../Constants.php';
require_once 'SuccessorFilter.php';
require_once 'BreedingChainNode.php';

class BackendHandler {
	//paremeter structure: pkmnObj, ..., eggGroup, otherEggGroup, eggGroupBlacklist, pkmnBlacklist

	/**
	 * main function that creates and returns the breeding tree
	 */
	public function createBreedingTree (): BreedingChainNode {
		//for performance measuring
		$timeStart = hrtime(true);

		$targetPkmnName = Constants::$targetPkmn;
		//contains the pkmn object from the external JSON data
		$targetPkmnData = Constants::$pkmnData->$targetPkmnName;

		//needed for preventing infinite recursion
		//a pkmn may only occur once in a branch, otherwise you would get an infinite loop
		$pkmnBlacklist = [$targetPkmnData->name];
		$eggGroupBlacklist = [];

		$breedingTree = $this->createBreedingChainNode(
			$targetPkmnData,
			$eggGroupBlacklist,
			$pkmnBlacklist
		);

		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		Constants::out("backend needed ".$timeDiff." seconds");

		return $breedingTree;
	}

	/** 
	 * depending on strictness pkmnBlacklist should use 
	 * 		pass by reference (stricter, faster, inaccurate) or 
	 * 		pass by value (looser, slower (in extreme cases more then 200 times slower), more accurate)
	 * 
	 * returns a BreedingChainNode object if pkmn can learn/inherit the move
	 * 		returns null if not
	 */
	private function createBreedingChainNode (
		StdClass $pkmnObj,
		Array $eggGroupBlacklist,
		Array $pkmnBlacklist
	): ?BreedingChainNode {
		//todo form change moves (e. g. Rotom) should count as normal 
		//todo (check if there are breedable pkmn that have those ^ form change learnsets)
		$node = new BreedingChainNode($pkmnObj->name);

		if ($this->canLearnNormally($pkmnObj)) {
			//if a pkmn can learn the targeted move directly without breeding no possible successors are needed/wanted
			//this happens at the end of a tree branch
			//todo if targetPkmn is able to learn targetMove directly, it looks like something went wrong (only targetPkmn's icon is displayed)
			return $node;
		}

		if ($this->canInherit($pkmnObj)) {
			//calls createBreedingChainNode(...) for all suiting parents (i. e. not in any blacklist)
			//	and adds them as a successor to chainNode if they can learn the move in some way
			$eggGroup2 = null;
			if (isset($pkmnObj->eggGroup2)) {
				//this prevents masses of debugging messages
				$eggGroup2 = $pkmnObj->eggGroup2;
			}

			$this->setPossibleParents(
				$node,
				$pkmnObj->eggGroup1,
				$eggGroup2,
				$eggGroupBlacklist,
				$pkmnBlacklist
			);

			//todo this explanation is not the yellow from the egg
			//if a pkmn has no successors that can learn the targeted move 
			//		(with the corresponding blacklists for the branch)
			//		it hasn't anyone to inherit the move from 
			//		--> branch doesn't get added to existing tree structure because there is no 'successful' end
			if (count($node->getSuccessors()) > 0) {
				//only return if the pkmn has at least one pkmn it can inherit the move from
				return $node;
			}
		}

		if ($this->canLearnViaEvent($pkmnObj)) {
			//event learnsets can be hard or impossible to get so they are only checked when there is no other way
	
			//marks that the chain node can only learn the move via event learnsets (needed for frontend) 
			$node->setLearnsByEvent();
			return $node;
		}

		return null;
	}

	/**
	 * calls setSuccessors for every eggGroup that's not been added to eggGroupBlacklist
	 * 
	 * depending on strictness pkmnBlacklist should use 
	 * pass by reference (stricter, faster, inaccurate) or 
	 * pass by value (looser, 
	 * 		slower (in extreme cases more then 200 times slower), more accurate)
	 */
	private function setPossibleParents (
		BreedingChainNode $node,
		String $eggGroup1,
		?String $eggGroup2,
		Array $eggGroupBlacklist,
		Array $pkmnBlacklist
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
		Array $pkmnBlacklist
	) {
		$eggGroupPkmnList = Constants::$eggGroups->$eggGroup;
		$filter = new SuccessorFilter($node, $pkmnBlacklist, $eggGroupPkmnList);
		$filteredList = $filter->filter();//the filter filters the unfiltered pkmn to get a filterd filter result

		foreach ($filteredList as $potSuccessorName) {
			$potSuccessorData = Constants::$pkmnData->$potSuccessorName;

			$eggGroupBlacklist[] = $eggGroup;

			$this->addSuccessor(
				$node,
				$potSuccessorData,
				$eggGroupBlacklist,
				$pkmnBlacklist
			);
		}
	}

	//todo name is meh (addSuccessor is already used in BreedingChainNode)
	private function addSuccessor (
		BreedingChainNode $node,
		StdClass $potSuccessorData,
		Array $eggGroupBlacklist,
		Array $pkmnBlacklist
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
	private function canLearnNormally (StdClass $pkmnObj) : bool {
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

		if (isset($pkmnObj->tutorLearnsets)) {
			$tutorLearnability = $this->checkLearnsetType($pkmnObj->tutorLearnsets);
			if ($tutorLearnability) {
				return true;
			}
		}

		return false;
	}

	private function canInherit (StdClass $pkmnObj) : bool {
		//not necessarily needed but it prevents masses of debug logs
		if (!isset($pkmnObj->breedingLearnsets)) {
			return false;
		}		

		return $this->checkLearnsetType($pkmnObj->breedingLearnsets);
	}

	private function canLearnViaEvent (StdClass $pkmnObj) : bool {
		//not necessarily needed but it prevents masses of debug logs
		if (!isset($pkmnObj->eventLearnsets)) {
			return false;
		}

		return $this->checkLearnsetType($pkmnObj->eventLearnsets);
	}

	private function checkLearnsetType (?Array $learnset) : bool {
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
}