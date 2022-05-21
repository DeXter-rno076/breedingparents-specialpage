<?php
require_once 'BreedingTreeNode.php';
require_once 'PkmnData.php';
require_once 'SuccessorFilter.php';
require_once 'SuccessorMixer.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';

class PkmnTreeNode extends BreedingTreeNode {
	public function __construct (string $pkmnName) {
		$this->data = new PkmnData($pkmnName);

		parent::__construct($this->data->getName());
	}

	public function createBreedingTreeNode (array $eggGroupBlacklist): ?BreedingTreeNode {
		Logger::statusLog('creating tree node of '.$this.' with eggGroupBlacklist: '.json_encode($eggGroupBlacklist));
		/*in this single spot parameter $eggGroupBlacklist MUST NOT be
		pass by reference (this would create wrong breeding trees, more information in the documentation)*/
		if ($this->data->canLearnDirectly()) {
			Logger::statusLog($this.' can learn the move directly');
			$this->learnabilityStatus->setLearnsDirectly();
			return $this;
		}

		if ($this->data->canLearnByOldGen()) {
			Logger::statusLog($this.' could learn the move in an old gen');
			/*learning by old gen might be easier to get than breeding but also maybe not.
			It depends on the user, therefore learnsByOldGen is checked and marked before
			inheritence check, but it only exits this method if the pkmn can't inherit the move
			(learning by old gen is in most cases easier than learning by event)*/
			$this->learnabilityStatus->setLearnsByOldGen();
		}

		if ($this->data->canLearnByBreeding()) {
			Logger::statusLog($this.' could inherit the move');
			$this->learnabilityStatus->setLearnsByBreeding();
			$this->selectSuccessors($eggGroupBlacklist);

			if ($this->hasSuccessors()) {
				Logger::statusLog($this.' can inherit the move, successors found');
				return $this;
			}
		}

		if ($this->learnabilityStatus->getLearnsByOldGen()) {
			return $this;
		}

		if ($this->data->canLearnByEvent()) {
			Logger::statusLog($this.' can learn the move by event');
			$this->learnabilityStatus->setLearnsByEvent();
			return $this;
		}

		Logger::statusLog($this.' can\'t learn the move');
		return null;
	}

	/**
	 * Looks which egg group(s) could 'deliver' possible breeding parents
	 * and calls the methods to check and add those pkmn.
	 */
	protected function selectSuccessors (array &$eggGroupBlacklist) {
		$this->selectSuccessorsOfMiddleOrEndNode($eggGroupBlacklist);

		$mixer = new SuccessorMixer($this);
		$this->successors = $mixer->mix($this->successors);
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
			return;
		}

		$this->createSuccessorsTreeSection($eggGroupBlacklist, $otherEggGroup);
	}

	/**
	 * Checks and adds the suiting pkmn of $whitelisted as the successors of this node
	 * 
	 * todo filtering and mixing are currently mixed
	 * 
	 * @param Array &$eggGroupBlacklist
	 * @param string $whitelisted currently handled egg group
	 */
	protected function createSuccessorsTreeSection (array &$eggGroupBlacklist, string $targetEggGroup) {
		Logger::statusLog('calling '.__FUNCTION__.' on '.$this.' eggGroupBlacklist: '
							.json_encode($eggGroupBlacklist));

		$eggGroupPkmn = Constants::$externalEggGroupsJSON->$targetEggGroup;
		$filter = new SuccessorFilter($eggGroupBlacklist, $this);
		$listOfPotentialSuccessors = $filter->filter($eggGroupPkmn);

		$eggGroupBlacklist[] = $targetEggGroup;

		foreach ($listOfPotentialSuccessors as $potSuccessorName) {
			$potSuccessorInstance = null;
			try {
				$potSuccessorInstance = new PkmnTreeNode($potSuccessorName);
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

	protected function breedingTreeNodeCanLearnTargetMove (?BreedingTreeNode $node): bool {
		return !is_null($node);
	}

	/**
	 * Looks for the non-blacklisted egg group of this pkmn and returns it
	 * if it finds one.
	 * @param Array $eggGroupBlacklist
	 * 
	 * @return string egg group that is not blacklisted or '' if none is found
	 */
	protected function getOtherEggGroup (array &$eggGroupBlacklist): string {
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

	protected function eggGroupIsBlacklisted (?string $eggGroup, array $eggGroupBlacklist): bool {
		if (is_null($eggGroup)) {
			return true;
		}
		return in_array($eggGroup, $eggGroupBlacklist);
	}

	public function buildIconName (): string {
		return 'Pokémon-Icon '.$this->data->getId().'.png';
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
		$msg .= ';;';

		return $msg;
	}
}