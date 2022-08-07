<?php
require_once __DIR__.'/../Pkmn.php';
require_once 'LearnabilityStatus.php';
require_once 'BreedingSubtree.php';

abstract class BreedingTreeNode extends Pkmn {
	//todo this belongs in PkmnTreeNode
	protected $learnabilityStatus;

    protected $data;
	
	protected function __construct (string $nodeTitle) {
		parent::__construct($nodeTitle);
		$this->learnabilityStatus = new LearnabilityStatus();
	}

	public abstract function createBreedingSubtree (array $eggGroupBlacklist): ?BreedingSubtree;

	public function getLearnabilityStatus (): LearnabilityStatus {
		return $this->learnabilityStatus;
	}

	//todo this doesnt belong in here but rather in a FrontendEntity sub class
	public abstract function buildIconName (): string;

    public abstract function getCorrectlyWrittenName(): string;

	public abstract function getLogInfo (): string;
}