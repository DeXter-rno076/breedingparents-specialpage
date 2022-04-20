<?php
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';
require_once __DIR__.'/../output_messages/ErrorMessage.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../Constants.php';

require_once 'PkmnData.php';
require_once 'BreedingTreeNode.php';

/**
 * list of relevant special cases:
 * 		gender unknown + male only
 *			all pkmn outside of the evo line are blacklisted
 *
 * 		female only
 * 			get blacklisted in gens 2-5
 * 
 * 		egg groupn unknown
 * 			have unpairable and unbreedable set to true
 * 			have all learnsets removed
 * 
 * 		babys
 * 			have unpairable set to true
 * 			have all learnsets except of breedingLearnsets removed
 * 
 * 		individual Pokemon:
 * 			Farbeagle
 * 				has all moves of the corresponding gen set in its directLearnsets
 *
 * 			Manaphy
 * 				manaphy has no breeding learnsets -> irrelevant
 */
class SuccessorFilter {
	private $eggGroupBlacklist;

	/**
	 * @var String egg group whose pkmn are checked and added after this filter did its job
	 * using a whitelist variable seems unnecessarily complex
	 * but is necessary (at least i haven't found a better solution).
	 * Just adding the egg group after filtering the successors would work
	 * for almost all nodes, BUT not for the root. There the first handled
	 * egg group could include the second egg group which would result in
	 * redundancies or a wrong tree section.
	 */
	private $whitelistedEggGroup;

	private $successorList;

	private $currentTreeNode;

	public function __construct (array $eggGroupBlacklist, string $whitelistedEggGroup,
									BreedingTreeNode $currentTreeNode) {
		Logger::statusLog('creating SuccessorFilter instance with: '
			.'eggGroupBlacklist: '.json_encode($eggGroupBlacklist)
			.'whiteListedEggGroup: '.json_encode($whitelistedEggGroup));
		$this->eggGroupBlacklist = $eggGroupBlacklist;
		$this->whitelistedEggGroup = $whitelistedEggGroup;
		$this->currentTreeNode = $currentTreeNode;

		if (isset(Constants::$externalEggGroupsJSON->$whitelistedEggGroup)) {
			$this->successorList = Constants::$externalEggGroupsJSON->$whitelistedEggGroup;
		} else {
			$this->successorList = [];
			Logger::elog('egg group '.$whitelistedEggGroup.' is not set');
		}
	}

	public function filter (): array {
		Logger::statusLog('filtering successor list');
		$this->removeNonExistants();
		$this->removeUnpairables();
		$this->checkGenderSpecificRequirements();
		$this->removeBlacklistedPkmn();
		$this->checkGenerationSpecificRequirements();
		Logger::statusLog('successor list after: '.json_encode($this->successorList));

		return $this->successorList;
	}

	private function removeNonExistants () {
		$doesNotExist = function (string $pkmnName): bool {
			try {
				$pkmnData = new PkmnData($pkmnName);
				return !$pkmnData->existsInThisGame();
			} catch (AttributeNotFoundException $e) {
				$errorMessage = new ErrorMessage($e);
				$errorMessage->output();
				return true;
			}
		};

		$this->remove($doesNotExist);
	}

	/**
	 * Removes all pkmn that are unpairable.
	 * This filter is supposed to only let those pkmn through that could be a suiting breeding parent.
	 * Pkmn that can't be paired can't possible be a parent.
	 */
	private function removeUnpairables () {
		/*unpairable pkmn cant get children
		=> may only appear at the end of a chain*/
		$isUnpairable = function (string $pkmnName): bool {
			$pkmnData = null;
			try {
				$pkmnData = new PkmnData($pkmnName);
			} catch (AttributeNotFoundException $e) {
				$errorMessage = new ErrorMessage($e);
				$errorMessage->output();
				return true;
			}
			$unpairableStatus = $pkmnData->isUnpairable();
			return $unpairableStatus;
		};

		$this->remove($isUnpairable);
	}

	/**
	 * Removes all successors for which $condition returns true.
	 * @param mixed $condition - function that returns a boolean and determines whether the given pkmn shall be removed
	 * 
	 */
	private function remove ($condition) {
		for ($i = 0; $i < count($this->successorList); $i++) {

			$pkmn = $this->successorList[$i];
			if ($condition($pkmn)) {
				array_splice($this->successorList, $i, 1);
				$i--;
			}

		}
	}

	private function checkGenderSpecificRequirements () {
		$currentTreeNodeData = $this->currentTreeNode->getJSONPkmnData();

		if ($currentTreeNodeData->isMaleOnly() || $currentTreeNodeData->hasNoGender()) {
			$this->remove(function (string $pkmnName): bool {
				$currentTreeNodeData = $this->currentTreeNode->getJSONPkmnData();
				return !$currentTreeNodeData->hasAsEvolution($pkmnName);
			});
		}
	}

	private function removeBlacklistedPkmn () {
		$pkmnIsBlacklisted = function (string $pkmn): bool {
			$pkmnData = null;
			try {
				$pkmnData = new PkmnData($pkmn);
			} catch (AttributeNotFoundException $e) {
				$errorMessage = new ErrorMessage($e);
				$errorMessage->output();
				return true;
			}

			if ($this->eggGroupIsWhiteListed($pkmnData->getEggGroup1())) {
				return false;
			}
			if ($this->eggGroupIsBlacklisted($pkmnData->getEggGroup1(), $pkmn)) {
				return true;
			}
			if ($pkmnData->hasSecondEggGroup()) {
				if ($this->eggGroupIsWhiteListed($pkmnData->getEggGroup2(), $pkmn)) {
					return false;
				}
				return $this->eggGroupIsBlacklisted($pkmnData->getEggGroup2(), $pkmn);
			}
			return false;
		};

		$this->remove($pkmnIsBlacklisted);
	}

	private function eggGroupIsWhiteListed (string $eggGroup): bool {
		if ($eggGroup === '') {
			return false;
		}
		return $this->whitelistedEggGroup === $eggGroup;
	}

	private function eggGroupIsBlacklisted (string $eggGroup): bool {
		if ($eggGroup === '') {
			return true;
		}
		return in_array($eggGroup, $this->eggGroupBlacklist);
	}

	/**
	 * Checks filters that only apply in some gens, like only male pkmn can pass on moves up to gen 5.
	 */
	private function checkGenerationSpecificRequirements () {
		if (Constants::$targetGenNumber < 6) {
			//in gens 2-5 only fathers can give moves to their kids
			$this->removeFemaleOnlys();
		}
	}

	private function removeFemaleOnlys () {
		$isFemale = function (string $pkmn): bool {
			$pkmnData = null;
			try {
				$pkmnData = new PkmnData($pkmn);
			} catch (AttributeNotFoundException $e) {
				$errorMessage = new ErrorMessage($e);
				$errorMessage->output();
				return true;
			}
			//female pkmn cant pass on moves from gen 2 - 5
			return $pkmnData->isFemaleOnly();
		};

		$this->remove($isFemale);
	}
}