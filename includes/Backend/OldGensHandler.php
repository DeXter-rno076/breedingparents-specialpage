<?php
require_once 'BackendHandler.php';
require_once 'GenHandlerInterface.php';

//only male pkmn can give moves to their kids

class OldGensHandler extends BackendHandler implements GenHandlerInterface {
	//basically like RecentGensHandler but checkSuccessorSpecialRequirements() is overwritten

	public function createBreedingChainNode (
		StdClass $pkmnData,
		Array &$pkmnBlacklist,
		Array $eggGroupBlacklist
	) : ?BreedingChainNode {}

	public function handleDirectLearnability (
		BreedingChainNode $pkmnObj
	) : BreedingChainNode {}

	public function handleInheritence (
		BreedingChainNode $pkmnObj,
		StdClass $pkmnData,
		Array $eggGroupBlacklist,
		Array &$pkmnBlacklist
	) : ?BreedingChainNode {}

	public function handleSpecialLearnability (
		BreedingChainNode $pkmnObj
	) : BreedingChainNode {}

	private function checkSuccessorSpecialRequirements (StdClass $successorObj) : bool {
		$gender = $successorObj->gender;
		if ($gender === 'both' || $gender === 'male') {
				return true;
		}
		return false;
	}
}