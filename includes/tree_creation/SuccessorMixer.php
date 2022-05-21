<?php
require_once 'BreedingTreeNode.php';
require_once __DIR__.'/../Constants.php';
require_once 'MiscTreeNode.php';

class SuccessorMixer {
	private $nodePkmn;
	private $targetMoveName;

	public function __construct (BreedingTreeNode $nodePkmn) {
		$this->nodePkmn = $nodePkmn;
		$this->targetMoveName = Constants::$targetMoveName;
	}
	
	public function mix (array $successorList): array {
		if ($this->isPichuVolttackleSpecialCase()) {
			$successorList = $this->addLightball($successorList);
		}
		return $successorList;
	}

	/**
	 * todo these magic numbers are unclean 
	 * */
	private function isPichuVolttackleSpecialCase (): bool {
		return $this->isSMOrNewer() && $this->nodePkmn->is('Pichu') && Constants::$targetMoveName === 'volttackle';
	}

	private function isSMOrNewer (): bool {
		if (Constants::$targetGenNumber > 3 || Constants::$targetGameString === 'SM') {
			return true;
		} else {
			return false;
		}
	}

	private function addLightball (array $successorList): array {
		$successorList[] = new MiscTreeNode('Kugelblitz', 'Itemicon Kugelblitz.png');
		return $successorList;
	}
}