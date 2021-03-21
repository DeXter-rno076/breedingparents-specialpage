<?php
class FrontendPreparator {
	private $pkmnData = null;
	private $PKMN_ICON_HEIGHT = -1;
	private $PKMN_MARGIN = 200;

	public function __construct ($pkmnData, $PKMN_ICON_HEIGHT) {
		$this->pkmnData = $pkmnData;
		$this->PKMN_ICON_HEIGHT = $PKMN_ICON_HEIGHT;
	}

	/**
	 * sets heights and y offsets for all pkmn nodes
	 * in the end it creates a new tree structure with objects that have only the least possible data needed for the SVG elements
	 */
	public function prepareForFrontend ($breedingTree) {
		//todo mark pkmn that have learnsByEvent set to true
		$this->setHeight($breedingTree, 1);

		$breedingTree->setTreeYOffset(0);
		$this->setOffset($breedingTree);

		$finalObjectTree = $this->buildFinalObjectTree($breedingTree);
		$finalObjectTree->generalHeight = $breedingTree->getTreeSectionHeight();

		return $finalObjectTree;
	}

	/**
	 * runs recursively over the object tree and sets all heights
	 * an object's height is the sum of its successors' heights or $PKMN_ICON_HEIGHT if it has no successors
	 */
	private function setHeight ($chainNode, $deepness) {
		if (count($chainNode->getSuccessors()) == 0) {
			$chainNode->setTreeSectionHeight($this->PKMN_ICON_HEIGHT);
			return $this->PKMN_ICON_HEIGHT;
		}

		$height = 0;

		foreach ($chainNode->getSuccessors() as $successor) {
			$height += $this->setHeight($successor, $deepness + 1);
		}

		$chainNode->setTreeSectionHeight($height);
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
			$offset = $chainNode->getTreeYOffset() + $takenSpace;
			$successor->setTreeYOffset($offset);
			$takenSpace += $successor->getTreeSectionHeight();
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
		$pkmnName = $breedingChainNode->getName();
		$pkmnData = $this->pkmnData->$pkmnName;
		$pkmnId = $pkmnData->id;
		$pkmnX = $currentDeepness * $this->PKMN_MARGIN;
		$pkmnY = $breedingChainNode->getTreeYOffset();
		if ($breedingChainNode->getTreeSectionHeight() > $this->PKMN_ICON_HEIGHT) {
			//this is only needed for pkmn with successors
			$pkmnY += ($breedingChainNode->getTreeSectionHeight() / 2);
		}

		$pkmnObj = new FrontendPkmnObj($pkmnId, $pkmnX, $pkmnY);

		foreach ($breedingChainNode->getSuccessors() as $successor) {
			$successorObject = $this->handleChainNode($successor, $currentDeepness + 1);
			$pkmnObj->addSuccessor($successorObject);
		}

		return $pkmnObj;
	}
}

class FrontendPkmnObj {
	//todo change access rights
	public $pkmnid;
	public $x;
	public $y;
	public $successors;

	public function __construct ($pkmnid, $x, $y) {
		$this->pkmnid = $pkmnid;
		$this->x = $x;
		$this->y = $y;
		$this->successors = [];
	}

	public function addSuccessor ($successor) {
		array_push($this->successors, $successor);
	}

	public function getSuccessors () {
		return $this->successors;
	}
}
?>