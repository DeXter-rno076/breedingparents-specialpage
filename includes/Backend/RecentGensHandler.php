<?php
require_once 'BackendHandler.php';
require_once 'GenHandlerInterface.php';
class RecentGensHandler extends BackendHandler implements GenHandlerInterface {
	/** 
	 * depending on strictness pkmnBlacklist should use 
	 * 		pass by reference (stricter, faster, inaccurate) or 
	 * 		pass by value (looser,
	 * 			slower (in extreme cases more then 200 times slower), more accurate)
	 * 
	 * returns a BreedingChainNode object if pkmn can learn/inherit the move
	 * 		returns null if not
	 */
	public function createBreedingChainNode (
		StdClass $pkmnObj,
		Array $eggGroupBlacklist,
		Array &$pkmnBlacklist
	) : ?BreedingChainNode {
		//todo form change moves (e. g. Rotom) should count as normal 
		//todo (check if there are breedable pkmn that have those ^ form change learnsets)
		$node = new BreedingChainNode($pkmnObj->name);

		if ($this->canLearnNormally($pkmnObj)) {
			return $this->handleDirectLearnability($node);
		}

		if ($this->canInherit($pkmnObj)) {
			$inheritanceCheckResult = $this->handleInheritance(
				$node,
				$pkmnObj,
				$eggGroupBlacklist,
				$pkmnBlacklist
			);

			if (!is_null($inheritanceCheckResult)) {
				return $inheritanceCheckResult;
			}
		}

		if ($this->canLearnViaEvent($pkmnObj)) {
			return $this->handleSpecialLearnability($node);
		}

		return null;
	}

	public function handleDirectLearnability (
		BreedingChainNode $node
	) : BreedingChainNode {
		//if a pkmn can learn the targeted move directly without breeding
		//		no possible successors are needed/wanted
		//this happens at the end of a tree branch
		//todo if targetPkmn is able to learn targetMove directly,
		//todo		it looks like something went wrong
		//todo		(only targetPkmn's icon is displayed)
		return $node;
	}

	public function handleInheritance (
		BreedingChainNode $node,
		StdClass $pkmnObj,
		Array $eggGroupBlacklist,
		Array &$pkmnBlacklist
	) : ?BreedingChainNode {
		//calls createBreedingChainNode(...) for all suiting parents
		//		(i. e. not in any blacklist)
		//		and adds them as a successor to chainNode
		//		if they can learn the move in some way
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
		//if a pkmn has no successors
		//		that can learn the targeted move 
		//		(with the corresponding blacklists for the branch)
		//		it hasn't anyone to inherit the move from 
		//		--> branch doesn't get added to existing tree structure
		//		because there is no 'successful' end
		if (count($node->getSuccessors()) > 0) {
			//only return if the pkmn has at least one pkmn it can inherit the move from
			return $node;
		}
	
		return null;
	}

	public function handleSpecialLearnability (
		BreedingChainNode $node
	) : BreedingChainNode {
		//similar to the canLearnNormally section a few lines before this
		//event learnsets can however be hard or impossible to get
		//		 so they only checked when there is no other way
	
		//marks that the chain node can only learn the move
		//		via event learnsets (needed for frontend) 
		$node->setLearnsByEvent();
		return $node;
	}
}