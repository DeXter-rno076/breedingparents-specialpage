<?php
class FrontendPreparator {
	private $breedingTreeDeepness = -1;
	private $pkmnData = null;
	private final $PKMN_ICON_HEIGHT = 10;

	public function __construct ($pkmnData) {
		$this->pkmnData = $pkmnData;
	}

	/**
	 * sets heights and y offsets for all pkmn nodes
	 * in the end it creates a new tree structure with objects that have only the least possible data needed for the SVG elements
	 */
	public function prepareForFrontend ($breedingTree) {
		//todo mark pkmn that have learnsByEvent set to true
		$this->setHeight($breedingTree);
		$this->setOffset($breedingTree);

		$finalObjectTree = $this->buildFinalObjectTree($breedingTree);
		$finalObjectTree->generalHeight = $breedingTree->treeSectionHeight;
		$finalObjectTree->maxDeepness = $this->breedingTreeDeepness;

		return $finalObjectTree;
	}
	
	/**
	 * runs recursively over the object tree and sets all heights
	 * an object's height is the sum of its successors' heights or $PKMN_ICON_HEIGHT if it has no successors
	 */
	private function setHeight ($chainNode, $deepness) {
		if ($this->breedingTreeDeepness < $deepness) {
			$this->breedingTreeDeepness = $deepness);
		}

		if (count($chainNode->getSuccessors()) == 0) {
			$chainNode->treeSectionHeight = $this->PKMN_ICON_HEIGHT;
			return $this->PKMN_ICON_HEIGHT;
		}

		$height = 0;

		foreach ($chainNode->getSuccessors() as $successor) {
			$height += $this->setHeight($successor, $deepness + 1);
		}

		$chainNode->treeSectionHeight = $height;
		return $height;
	}

	/**
	 * runs recursively over the object tree and sets all y offsets
	 * in one function call the offsets for chainNode's successors are set by saving 
	 * the - by the previous succesors - already taken space and adding the successor's height afterwards
	 */
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

	/**
	 * calculates the node's x coordinate in %
	 * calculates the node's y coordinate in pixel
	 * creates new object for the final svg object structure
	 */
	private function handleChainNode ($breedingChainNode, $currentDeepness) {
		$pkmnId = $this->pkmnData->$breedingChainNode->name->id;
		$pkmnX = $currentDeepness * (100 / $this->breedingTreeDeepness);
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