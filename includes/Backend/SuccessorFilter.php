<?php
require_once __DIR__.'/../Constants.php';
class SuccessorFilter {
	/*
	has a function similar to a real world filter
	takes a list of possible successors (i. e. an egg group list)
	and removes every pkmn that isn't compatible to the situation
		generally all pkmn that can't breed
		pkmn that only have one gender aren't an option in some cases
	*/
	private BreedingChainNode $node;
	private Array $eggGroupBlacklist;
	private String $whitelistedEggGroup;
	private Array $successorList;
	
	public function __construct (
		BreedingChainNode $node,
		Array $eggGroupBlacklist,
		String $whitelistedEggGroup,
		Array $successorList
	) {
		$this->node = $node;
		$this->eggGroupBlacklist = $eggGroupBlacklist;
		$this->whitelistedEggGroup = $whitelistedEggGroup;
		$this->successorList = $successorList;
	}

	public function filter (): Array {
		$this->removeBlacklistedPkmn();
		//$this->removeUnbreedables();
		//$this->checkGenSpecificRequirements();

		return $this->successorList;
	}

	private function removeBlacklistedPkmn () {
		$this->remove(function ($pkmn) {
			$pkmnData = Constants::$pkmnData->$pkmn;
			if ($pkmnData->eggGroup1 === $this->whitelistedEggGroup) {
				return false;
			}
			if (in_array($pkmnData->eggGroup1, $this->eggGroupBlacklist)) {
				return true;
			}
			if (isset($pkmnData->eggGroup2)) {
				if ($pkmnData->eggGroup2 === $this->whitelistedEggGroup) {
					return false;
				}
				return in_array($pkmnData->eggGroup2, $this->eggGroupBlacklist);
			}
			return false;
		});
	}

	private function removeUnbreedables () {
		$this->remove(function ($pkmn) {
			return in_array($pkmn, Constants::$unbreedable);
		});
	}

	private function checkGenSpecificRequirements () {
		if (Constants::$targetGen < 6) {
			//in gens 2-5 only fathers can give moves to their kids
			$this->checkGenders();
		}
	}

	private function checkGenders () {
		$this->remove(function ($pkmn) {
			//TODO not sure whether this works
			$gender = Constants::$pkmnData->$pkmn->gender;
			return $gender !== 'both' && $gender !== 'male';
		});
	}
	
	private function remove ($condition) {
		for($i = 0; $i < count($this->successorList); $i++) {
			$pkmn = $this->successorList[$i];
			if ($condition($pkmn)) {
				array_splice($this->successorList, $i, 1);
				$i--;
			}
		}
	}
}