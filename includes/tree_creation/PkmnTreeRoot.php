<?php
require_once 'BreedingTreeNode.php';
require_once 'PkmnTreeNode.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';
require_once __DIR__.'/../output_messages/ErrorMessage.php';

class PkmnTreeRoot extends PkmnTreeNode {
	public function __construct (string $pkmnName) {
		parent::__construct($pkmnName);
	}

	/**
	 * @param array $eggGroupBlacklist - is ignored because tree roots always start a new blacklist
	 */
	public function createBreedingTreeNode (array $eggGroupBlacklist): BreedingTreeNode {
		Logger::statusLog('creating tree root node of '.$this);
		if ($this->data->canLearnDirectly()) {
			Logger::statusLog($this.' can learn the move directly');
			$this->learnabilityStatus->setLearnsDirectly();
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
			$emptyEggGroupBlacklist = [];
			$this->selectSuccessors($emptyEggGroupBlacklist);

			if ($this->hasSuccessors()) {
				Logger::statusLog($this.' can inherit the move, successors found');
			}
		} else {
			$this->tryBreedingChainOverLowestEvolution();
		}

		if ($this->data->canLearnByEvent()) {
			Logger::statusLog($this.' can learn the move by event');
			$this->learnabilityStatus->setLearnsByEvent();
		}
		
		return $this;
	}

	protected function selectSuccessors (array &$eggGroupBlacklist) {
		$this->selectSuccessorsOfRootNode();

		$mixer = new SuccessorMixer($this);
		$this->successors = $mixer->mix($this->successors);
	}

	private function selectSuccessorsOfRootNode () {
		$eggGroup1 = $this->data->getEggGroup1();

		$firstSubTreeBlacklist = [];
		$secondSubTreeBlacklist = [$eggGroup1];

		if ($this->data->hasSecondEggGroup()) {
			$firstSubTreeBlacklist[] = $this->data->getEggGroup2();
		}

		$this->createSuccessorsTreeSection($firstSubTreeBlacklist, $eggGroup1);
		if ($this->data->hasSecondEggGroup()) {
			$eggGroup2 = $this->data->getEggGroup2();

			$this->createSuccessorsTreeSection(
				$secondSubTreeBlacklist, $eggGroup2);
		}
	}

	/**
	 * If the tree root is an evolution it can't inherit any moves (because it can't be breeded directly).
	 * So inheritance is tried on its lowest evo.
	 */
	private function tryBreedingChainOverLowestEvolution () {
		if ($this->data->isLowestEvolution()) {
			return;
		}
		Logger::statusLog($this.' is not the lowest evolution in it\'s line, trying an evo connection');

		$lowestEvoInstance = null;
		try {
			$lowestEvoName = $this->data->getLowestEvo();
			$lowestEvoInstance = new PkmnTreeRoot($lowestEvoName);
		} catch (AttributeNotFoundException $e) {
			$errorMessage = new ErrorMessage($e);
			$errorMessage->output();
			return;
		}

		$lowestEvoNode = $lowestEvoInstance->createBreedingTreeNode([]);
		if ($lowestEvoNode->getLearnabilityStatus()->canLearn()) {
			if ($this->breedingChainOverLowestEvolutionHasAnAdvantage($lowestEvoNode)) {
				$this->addSuccessor($lowestEvoNode);
				//todo learnsByBreeding is unclean here
				$this->learnabilityStatus->setLearnsByBreeding();
			}
		}
	}

	private function breedingChainOverLowestEvolutionHasAnAdvantage (BreedingTreeNode $lowestEvoNode) {
		if ($lowestEvoNode->hasSuccessors()) {
			return true;
		}
		/*NOT ( this learns by old gen AND evo learns by old gen or event)
		without negation at the beginning:
		-> this does not learn via old gen or evo learns neither by old gen nor event*/
		return ! ($this->learnabilityStatus->getLearnsByOldGen() 
			&& ($lowestEvoNode->learnabilityStatus->getLearnsByOldGen() 
				|| $lowestEvoNode->learnabilityStatus->getLearnsByEvent()));
	}

	public function getLogInfo (): string {
		$normalPkmnNodeLogInfo = parent::getLogInfo();
		return str_replace(';;', 'root;;', $normalPkmnNodeLogInfo);
	}
}