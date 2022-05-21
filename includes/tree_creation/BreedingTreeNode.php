<?php
require_once __DIR__.'/../Pkmn.php';
require_once 'LearnabilityStatus.php';

abstract class BreedingTreeNode extends Pkmn {
	//todo this belongs in PkmnTreeNode
	protected $learnabilityStatus;
	
	protected $successors = [];
	protected $data;
	
	protected function __construct (string $nodeTitle) {
		parent::__construct($nodeTitle);
		$this->learnabilityStatus = new LearnabilityStatus();
	}

	public abstract function createBreedingTreeNode (array $eggGroupBlacklist): ?BreedingTreeNode;

	public function getLearnabilityStatus (): LearnabilityStatus {
		return $this->learnabilityStatus;
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

	public function getSuccessors (): array {
		return $this->successors;
	}

	protected function addSuccessor (BreedingTreeNode $successor) {
		$this->successors[] = $successor;
	}

	//todo this doesnt belong in here but rather in a FrontendEntity sub class
	public abstract function buildIconName (): string;

	public abstract function getLogInfo (): string;
}