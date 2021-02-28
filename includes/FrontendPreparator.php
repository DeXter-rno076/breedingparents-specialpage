<?php
class FrontendPreparator {
	private $breedingTreeDeepness = -1;
	private $svgWidth;
	private $pkmnData = null;

	public function __construct ($pkmnData) {
		$this->pkmnData = $pkmnData;
	}

	public function prepareForFrontend ($breedingTree) {
		//todo breedingTreeDeepness
		//todo svgWidth
		//todo mark pkmn that have learnsByEvent set to true
		$this->setHeight($breedingTree);
		$this->setOffset($breedingTree);

		$finalObjectTree = $this->buildFinalObjectTree($breedingTree);

		return $finalObjectTree;
	}
	
	private function setHeight ($chainNode) {
		//todo involve pkmn icon heights
		if (count($chainNode->getSuccessors()) == 0) {
			$chainNode->treeSectionHeight = 1;
			return 1;
		}

		$height = 0;

		foreach ($chainNode->getSuccessors() as $successor) {
			$height += $this->setHeight($successor);
		}

		$chainNode->treeSectionHeight = $height;
		return $height;
	}

	private function setOffset ($chainNode) {
		$takenSpace = 0;

		foreach ($chainNode->getSuccessors() as $successor) {
			$offset = $chainNode->treeYOffset + $takenSpace;
			$successor->treeYOffset = $offset;
			$takenSpace += $successor->$treeSectionHeight;
			$this->setOffset($successor);
		}
	}

	private function buildFinalObjectTree ($breedingTree) {
		$result = $this->handleChainNode($breedingTree, 0);

		return $result;
	}

	private function handleChainNode ($breedingChainNode, $currentDeepness) {
		$pkmnId = $this->pkmnData->$breedingChainNode->name->id;
		$pkmnX = $currentDeepness * ($this->svgWidth / $this->breedingTreeDeepness);
		$pkmnY = $breedingChainNode->treeYOffset + ($breedingChainNode->treeSectionHeight / 2);
		$pkmnObj = new FrontendPkmnObj($pkmnId, $pkmnX, $pkmnY);

		foreach ($breedingChainNode->getSuccessors() as $successor) {
			$successorObject = $this->handleChainNode($successor, $currentDeepness + 1);
			$pkmnObj->addSuccessor($successorObject);
		}

		return $pkmnObj;
	}
}

class FrontendPkmnObj {
	public $pkmnid;
	public $x;
	public $y;
	public $successors;

	public function __construct ($pkmnid, $x, $y) {
		$this->pkmnid = $pkmnid;
		$this->x = $x;
		$this->y = $y;
	}

	public function addSuccessor ($successor) {
		array_push($this->successors, $successor);
	}
}
?>