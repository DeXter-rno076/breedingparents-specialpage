<?php
require 'BackendHandler.php';
class Gen7Handler extends BackendHandler {
	/** 
	 * depending on strictness pkmnBlacklist should use 
	 * pass by reference (stricter, faster, inaccurate) or 
	 * pass by value (looser, slower (in extreme cases more then 200 times slower), more accurate)
	 * 
	 * returns a BreedingChainNode objetc if pkmn can learn/inherit the move
	 * returns null if not
	 */
	public function createBreedingChainNode ($pkmn, &$pkmnBlacklist, $eggGroupBlacklist) {
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
}
?>