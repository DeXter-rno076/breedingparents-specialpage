<?php
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';
require_once __DIR__.'/../output_messages/ErrorMessage.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../Constants.php';

require_once 'PkmnData.php';
require_once 'PkmnTreeNode.php';
require_once 'BreedingTreeNode.php';

/**
 * explanation prefix: This is a **Successor**filter. This removes every Pokemon that is not intended as
 * a **parent** of the currently handled tree node.
 * 
 * list of relevant special cases:
 * 		gender unknown + male only
 * 			only with ditto they can have children of their species -> cant be influenced by other species
 * 			-> if all parents of the current node are male/unknown onle -> only parents of its evo line are allowed
 * todo     -> gender unknown or male only successors are only allowed if node is in the same evo line 
 *
 * 		female only
 * 			can only have their own species as children -> cant influence other species
 * 			-> are only allowed if current node is the lowest evo of them (reminder: middle nodes are always lowest evos)
 * 			females can't pass on any moves up until gen 5 -> get blacklisted in gens 2-5
 * 
 * 		egg groupn unknown
 * 			don't take part in any relevant breeding situation (special case Phione doesn't inherit any moves) 
 * 			-> always removed
 * 
 * 		babys
 * 			can't take part in making kids -> not allowed as a parent -> get removed
 * 
 * 		individual Pokemon:
 * 			Farbeagle
 * 				has all moves of the corresponding gen set in its directLearnsets
 *
 * 			Phione
 * 				manaphy has no breeding learnsets -> irrelevant
 */
/**
 * todo this class not only filters but also creates the successor list -> separate tasks and create super class for filter and mixer
 */
class SuccessorFilter {
	private $eggGroupBlacklist;
	private $currentPkmnTreeNodeData;

	/**
	 * @throws AttributeNotFoundException
	 */
	public function __construct (array $eggGroupBlacklist, PkmnTreeNode $currentPkmnTreeNode) {
		Logger::statusLog('creating SuccessorFilter instance with: '
			.'eggGroupBlacklist: '.json_encode($eggGroupBlacklist));
		$this->eggGroupBlacklist = $eggGroupBlacklist;
		$this->currentPkmnTreeNodeData = new PkmnData($currentPkmnTreeNode->getName());
	}

	public function filter (array $successorList): array {
		Logger::statusLog('filtering successor list; successor list before: '.json_encode($successorList));
		$successorList = $this->removeNonExistants($successorList);
		$successorList = $this->removeUnpairables($successorList);
		$successorList = $this->checkGenderSpecificRequirements($successorList);
		$successorList = $this->removeBlacklistedPkmn($successorList);
		$successorList = $this->checkGenerationSpecificRequirements($successorList);
		Logger::statusLog('successor list after: '.json_encode($successorList));

		return $successorList;
	}

	/**
	 * Removes all successors for which $condition returns true.
	 * @param mixed $condition - function that returns a boolean and determines whether the given pkmn shall be removed
	 * 
	 */
	private function remove (array $list, $condition): array {
		for ($i = 0; $i < count($list); $i++) {
			$item = $list[$i];
			if ($condition($item)) {
                if (Constants::$createDetailedSuccessorFilterLogs) {
                    Logger::statusLog('removing '.$item);
                }
				array_splice($list, $i, 1);
				$i--;
			}
		}

		return $list;
	}

	private function removeNonExistants (array $successorList): array {
        if (Constants::$createDetailedSuccessorFilterLogs) {
            Logger::statusLog('removing pkmn that dont exist in this game');
        }
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

		return $this->remove($successorList, $doesNotExist);
	}

	/**
	 * Removes all pkmn that are unpairable.
	 * This filter is supposed to only let those pkmn through that could be a suiting breeding parent.
	 * Pkmn that can't be paired can't possible be a parent.
	 */
	private function removeUnpairables (array $successorList): array {
        if (Constants::$createDetailedSuccessorFilterLogs) {
            Logger::statusLog('removing unpairable pkmn (i. e. that cant get children)');
        }
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

		return $this->remove($successorList, $isUnpairable);
	}

	private function checkGenderSpecificRequirements (array $successorList): array {
		$successorList = $this->checkMaleAndUnknownOnlyRequirements($successorList);
		$successorList = $this->checkFemaleOnlyRequirements($successorList);
		return $successorList;
	}

    //todo split
	private function checkMaleAndUnknownOnlyRequirements (array $successorList): array {
		if ($this->nodeHasOnlyMaleEvolutions() || $this->nodeHasOnlyGenderUnknownEvolutions()) {
            if (Constants::$createDetailedSuccessorFilterLogs) {
                Logger::statusLog('removing all non-evo-line successors because target node has'
                                .' only male-only or gender-unknown evos');
            }
			return $this->remove($successorList, function (string $pkmnName): bool {
				return !$this->currentPkmnTreeNodeData->hasAsEvolution($pkmnName);
			});
		} else {
            if (Constants::$createDetailedSuccessorFilterLogs) {
                Logger::statusLog('removing all male-only or gender-unknown successors'
                                    .' that are no evos of the target node');
            }
            return $this->remove($successorList, function (string $potSuccessorName): bool {
                try {
                    $potSuccessorData = new PkmnData($potSuccessorName);
                    if ($potSuccessorData->isMaleOnly() || $potSuccessorData->hasNoGender()) {
                        return !$this->currentPkmnTreeNodeData->hasAsEvolution($potSuccessorName);
                    } else {
                        return false;
                    }
                } catch (Exception $e) {
                    $eMsg = new ErrorMessage($e);
                    $eMsg->output();
                    return false;
                }
            });
        }
	}

	//basically duplicate of $this->nodeHasOnlyGenderUnknownEvolutions
	private function nodeHasOnlyMaleEvolutions (): bool {
        $evos = $this->currentPkmnTreeNodeData->getEvolutions();
        if (count($evos) === 0) {
            return false;
        }
		foreach ($evos as $evoName) {
			try {
				$evoData = new PkmnData($evoName);
				if (!$evoData->isMaleOnly()) {
					return false;
				}
			} catch (AttributeNotFoundException $e) {
				$eMsg = new ErrorMessage($e);
				$eMsg->output();
			}
		}
		return true;
	}

	//basically duplicate of $this->nodeHasOnlyMaleEvolutions
	private function nodeHasOnlyGenderUnknownEvolutions (): bool {
        $evos = $this->currentPkmnTreeNodeData->getEvolutions();
        if (count($evos) === 0) {
            return false;
        }
		foreach ($evos as $evoName) {
			try {
				$evoData = new PkmnData($evoName);
				if (!$evoData->hasNoGender()) {
					return false;
				}
			} catch (AttributeNotFoundException $e) {
				$eMsg = new ErrorMessage($e);
				$eMsg->output();
			}
		}
		return true; 
	}

    //todo more precise method name
	private function checkFemaleOnlyRequirements (array $successorList): array {
        if (Constants::$createDetailedSuccessorFilterLogs) {
            Logger::statusLog('removing female-only successors that are no evos of the target node');
        }
		return $this->remove($successorList, function (string $pkmnName) {
			try {
				$pkmnData = new PkmnData($pkmnName);
				return $pkmnData->isFemaleOnly() && !$this->currentPkmnTreeNodeData->hasAsEvolution($pkmnName);
			} catch (AttributeNotFoundException $e) {
				$eMsg = new ErrorMessage($e);
				$eMsg->output();
				return false;
			}
		});
	}

	private function removeBlacklistedPkmn (array $successorList): array {
        if (Constants::$createDetailedSuccessorFilterLogs) {
            Logger::statusLog('removing successors that have a blacklisted egg group');
        }
		$pkmnIsBlacklisted = function (string $pkmn): bool {
			$pkmnData = null;
			try {
				$pkmnData = new PkmnData($pkmn);
			} catch (AttributeNotFoundException $e) {
				$errorMessage = new ErrorMessage($e);
				$errorMessage->output();
				return true;
			}

			if ($this->eggGroupIsBlacklisted($pkmnData->getEggGroup1(), $pkmn)) {
				return true;
			}
			if ($pkmnData->hasSecondEggGroup()) {
				return $this->eggGroupIsBlacklisted($pkmnData->getEggGroup2(), $pkmn);
			}
			return false;
		};

		return $this->remove($successorList, $pkmnIsBlacklisted);
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
	private function checkGenerationSpecificRequirements (array $successorList): array {
		if (Constants::$targetGenNumber < 6) {
            if (Constants::$createDetailedSuccessorFilterLogs) {
                Logger::statusLog('removing female-only successors because they cant pass on moves in gens 2-5');
            }
			//in gens 2-5 only fathers can give moves to their kids
			$successorList = $this->removeFemaleOnlys($successorList);
		}
		return $successorList;
	}

	private function removeFemaleOnlys (array $successorList): array {
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

		return $this->remove($successorList, $isFemale);
	}
}