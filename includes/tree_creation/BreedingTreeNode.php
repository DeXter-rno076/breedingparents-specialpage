<?php
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';
require_once __DIR__.'/../output_messages/ErrorMessage.php';
require_once __DIR__.'/../Pkmn.php';
require_once __DIR__.'/../Logger.php';

require_once 'PkmnData.php';
require_once 'SuccessorFilter.php';

class BreedingTreeNode extends Pkmn {
	private $isRoot = false;
	private $successors = [];

	private $data;

	private $learnsByEvent = false;
	private $learnsByOldGen = false;

	public function __construct (string $pkmnName, bool $isRoot = false) {
		$this->data = new PkmnData($pkmnName);

		parent::__construct($this->data->getName(), $this->data->getID());

		$this->isRoot = $isRoot;
	}

	public function createBreedingTreeNode (array $eggGroupBlacklist): ?BreedingTreeNode {
		Logger::statusLog('creating tree node of '.$this.' with eggGroupBlacklist: '.json_encode($eggGroupBlacklist));
		/*in this single spot parameter $eggGroupBlacklist MUST NOT be
		pass by reference (this would create wrong breeding trees, more information in the documentation)*/
		if ($this->data->canLearnDirectly()) {
			Logger::statusLog($this.' can learn the move directly');
			return $this;
		}

		if ($this->data->canLearnByOldGen()) {
			Logger::statusLog($this.' could learn the move in an old gen');
			/*learning by old gen might be easier to get than breeding but also maybe not.
			It depends on the user, therefore learnsByOldGen is checked and marked before
			inheritence check, but it only exits this method if the pkmn can't inherit the move
			(learning by old gen is in most cases easier than learning by event)*/
			$this->setLearnsByOldGen();
		}

		if ($this->data->canInherit()) {
			Logger::statusLog($this.' could inherit the move');
			$this->selectSuccessors($eggGroupBlacklist);

			if ($this->hasSuccessors()) {
				Logger::statusLog($this.' can inherit the move, successors found');
				return $this;
			}
		} else if ($this->isRoot) {
			$result = $this->tryBreedingChainOverLowestEvolution();
			if (!is_null($result)) {
				return $result;
			}
		}

		if ($this->getLearnsByOldGen()) {
			return $this;
		}

		if ($this->data->canLearnByEvent()) {
			Logger::statusLog($this.' can learn the move by event');
			$this->setLearnsByEvent();
			return $this;
		}

		Logger::statusLog($this.' can\'t learn the move');
		return null;
	}

	/**
	 * Looks which egg group(s) could 'deliver' possible breeding parents
	 * and calls the methods to check and add those pkmn.
	 * 	 */
	private function selectSuccessors (array &$eggGroupBlacklist) {
		if ($this->isRoot) {
			$this->selectSuccessorsOfRootNode($eggGroupBlacklist);
		} else {
			$this->selectSuccessorsOfMiddleOrEndNode($eggGroupBlacklist);
		}		
	}

	private function selectSuccessorsOfRootNode (array &$eggGroupBlacklist) {
		$eggGroup1 = $this->data->getEggGroup1();

		/*root pkmn is at the start => it is the only pkmn that
		doesnt need an egg group for a connection to a predecessor
		because it doesn't have one*/
		$eggGroupBlacklist[] = $eggGroup1;

		if ($this->data->hasSecondEggGroup()) {
			$eggGroup2 = $this->data->getEggGroup2();
			/*egg group 2 has to be called first because
			both method calls need both egg groups in the blacklist*/
			$eggGroupBlacklist[] = $eggGroup2;

			$this->createSuccessorsTreeSection(
				$eggGroupBlacklist, $eggGroup2);
		}
		$this->createSuccessorsTreeSection($eggGroupBlacklist, $eggGroup1);
	}

	/**
	 * Checks and adds the suiting pkmn of $whitelisted as the successors of this node
	 * @param Array &$eggGroupBlacklist
	 * @param string $whitelisted currently handled egg group
	 */
	private function createSuccessorsTreeSection (array &$eggGroupBlacklist, string $whitelisted) {
		Logger::statusLog('calling '.__FUNCTION__.' on '.$this.' eggGroupBlacklist: '
							.json_encode($eggGroupBlacklist).', whitelisted: '.$whitelisted);

		$filter = new SuccessorFilter($eggGroupBlacklist, $whitelisted, $this);
		$listOfPotentialSuccessors = $filter->filter();

		foreach ($listOfPotentialSuccessors as $potSuccessor) {
			$potSuccessorInstance = null;
			try {
				$potSuccessorInstance = new BreedingTreeNode($potSuccessor);
			} catch (AttributeNotFoundException $e) {
				Logger::elog('couldn\'t create breeding tree node, error: '.$e);
				continue;
			}
			$potSuccessorNode = $potSuccessorInstance->createBreedingTreeNode($eggGroupBlacklist);

			if ($this->breedingTreeNodeCanLearnTargetMove($potSuccessorNode)) {
				$this->addSuccessor($potSuccessorNode);
			}
		}
	}

	private function breedingTreeNodeCanLearnTargetMove (?BreedingTreeNode $node): bool {
		return !is_null($node);
	}

	private function addSuccessor (BreedingTreeNode $newSuccessor) {
		array_push($this->successors, $newSuccessor);
	}

	private function selectSuccessorsOfMiddleOrEndNode (array &$eggGroupBlacklist) {
		if (!$this->data->hasSecondEggGroup()) {
			/*One egg group is ALWAYS in the blacklist (except for
			root node pkmn).
			If the pkmn has only one egg group it MUST learn the move directly
			in order to be a suitable parent.
			To be able to be in the middle of a breeding chain a pkmn
			needs two egg groups (one for successor(s), one for predecessor).
			A pkmn with one egg group can ONLY be the end of a chain
			(=> must learn the move directly).*/
			return;
		}

		$otherEggGroup = $this->getOtherEggGroup($eggGroupBlacklist);
		if ($otherEggGroup === '') {
			Logger::wlog('selectSuccessors call on '.$this.', somehow '
				.'getOtherEggGroup returned null, even though this case '
				.'should have already been covered by an if block');
			return;
		}

		$eggGroupBlacklist[] = $otherEggGroup;
		$this->createSuccessorsTreeSection($eggGroupBlacklist, $otherEggGroup);
	}

	/**
	 * Looks for the non-blacklisted egg group of this pkmn and returns it
	 * if it finds one.
	 * @param Array $eggGroupBlacklist
	 * 
	 * @return string egg group that is not blacklisted or '' if none is found
	 */
	private function getOtherEggGroup (array &$eggGroupBlacklist): string {
		Logger::statusLog('calling '.__FUNCTION__.' on '.$this.' eggGroupBlacklist: '
							.json_encode($eggGroupBlacklist));
		/*One egg group is always in the blacklist because of
		the connection to the predecessor.
		Possible successors can only come from the other one.*/
		$eggGroup1 = $this->data->getEggGroup1();
		$eggGroup2 = $this->data->getEggGroup2();
		$otherEggGroup = '';

		if (!$this->eggGroupIsBlacklisted($eggGroup1, $eggGroupBlacklist)) {
			Logger::statusLog('egg group 1 '.$eggGroup1.' found for '.$this);
			$otherEggGroup = $eggGroup1;

		} else if ($this->data->hasSecondEggGroup() 
					&& !$this->eggGroupIsBlacklisted($eggGroup2, $eggGroupBlacklist)) {
			Logger::statusLog('egg group 2 '.$eggGroup2.' found for '.$this);
			$otherEggGroup = $eggGroup2;

		} else {
			//all egg groups blocked
			Logger::statusLog('all egg groups of '.$this. ' are blocked');
			return '';
		}

		Logger::statusLog('returning '.$otherEggGroup);
		return $otherEggGroup;
	}

	private function eggGroupIsBlacklisted (?string $eggGroup, array $eggGroupBlacklist): bool {
		if (is_null($eggGroup)) {
			return true;
		}
		return in_array($eggGroup, $eggGroupBlacklist);
	}

	/**
	 * Checks whether this node has successors by counting its successors
	 * and returning amount greater 0.
	 * 
	 * todo this can probably be outsourced. FrontendPkmn and SVGPkmn use a pendant
	 * => some super class like Node if Pkmn can't be used for it
	 */
	public function hasSuccessors (): bool {
		return count($this->successors) > 0;
	}

	/**
	 * If the tree root is an evolution it can't inherit any moves (because it can't be breeded directly).
	 * So inheritance is tried on its lowest evo.
	 */
	private function tryBreedingChainOverLowestEvolution (): ?BreedingTreeNode {
		if ($this->data->isLowestEvolution()) {
			return null;
		}
		Logger::statusLog($this.' is not the lowest evolution in it\'s line, trying an evo connection');

		$lowestEvoInstance = null;
		try {
			$lowestEvoIsRoot = true;
			$lowestEvoInstance = $this->data->getLowestEvolutionBreedingTreeInstance($lowestEvoIsRoot);
		} catch (AttributeNotFoundException $e) {
			$errorMessage = new ErrorMessage($e);
			$errorMessage->output();
			return null;
		}

		$lowestEvoNode = $lowestEvoInstance->createBreedingTreeNode([]);
		if (!is_null($lowestEvoNode)) {
			if ($this->breedingChainOverLowestEvolutionHasAnAdvantage($lowestEvoNode)) {
				$this->addSuccessor($lowestEvoNode);
				return $this;
			}
		}
		return null;
	}

	private function breedingChainOverLowestEvolutionHasAnAdvantage (BreedingTreeNode $lowestEvoNode) {
		if ($lowestEvoNode->hasSuccessors()) {
			return true;
		}
		/*NOT ( this learns by old gen AND evo learns by old gen or event)
		without negation at the beginning:
		-> this does not learn via old gen or evo learns neither by old gen nor event*/
		return ! ($this->getLearnsByOldGen() 
			&& ($lowestEvoNode->getLearnsByOldGen() || $lowestEvoNode->getLearnsByEvent()));
	}

	public function getSuccessors (): array {
		return $this->successors;
	}

	private function setLearnsByEvent () {
		$this->learnsByEvent = true;
	}

	public function getLearnsByEvent (): bool {
		return $this->learnsByEvent;
	}

	private function setLearnsByOldGen () {
		$this->learnsByOldGen = true;
	}

	public function getLearnsByOldGen (): bool {
		return $this->learnsByOldGen;
	}

	public function isRoot (): bool {
		return $this->isRoot;
	}

	public function getJSONPkmnData (): PkmnData {
		return $this->data;
	}

	/**
	 * @return string BreedingTreeNode:<pkmn name>;<successor count>;<learns by event>;<learns by old gen>;<is root>;;
	 */
	public function getLogInfo (): string {
		$msg = 'BreedingTreeNode:\'\'\''.$this->data->getName().'\'\'\';'.count($this->successors);
		if (isset($this->learnsByEvent) && $this->learnsByEvent) {
			$msg .= ';learnsByEvent';
		}
		if (isset($this->learnsByOldGen) && $this->learnsByOldGen) {
			$msg .= ';learnsByOldGen';
		}
		if ($this->isRoot) {
			$msg .= ';isRoot';
		}
		$msg .= ';;';

		return $msg;
	}
}