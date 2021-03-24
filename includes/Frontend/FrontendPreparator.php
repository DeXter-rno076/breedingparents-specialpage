<?php
require 'FrontendPkmnObj.php';
class FrontendPreparator {
	private $pkmnData = null;

	//temporary
	private $PKMN_ICON_HEIGHT = -1;
	
	//space between each pkmn 'column'
	private $PKMN_MARGIN = 200;

	public function __construct ($pkmnData, $PKMN_ICON_HEIGHT) {
		$this->pkmnData = $pkmnData;
		$this->PKMN_ICON_HEIGHT = $PKMN_ICON_HEIGHT;
	}

	/**
	 * sets heights and y offsets for all pkmn nodes
	 * in the end it creates a new tree structure with objects 
	 * 		that have only the least possible data needed for the SVG elements
	 */
	public function prepareForFrontend ($breedingTree) {
		//todo mark pkmn that have learnsByEvent set to true
		$this->setHeight($breedingTree, 1);

		//the whole tree obviously starts at the top
		$breedingTree->setTreeYOffset(0);
		$this->setOffset($breedingTree);

		$finalObjectTree = $this->buildFinalObjectTree($breedingTree);
		$finalObjectTree->svgTagHeight = $breedingTree->getTreeSectionHeight();

		return $finalObjectTree;
	}

	/**
	 * runs recursively over the object tree and sets all heights
	 * an object's height is the sum of its successors' heights
	 * 		or $PKMN_ICON_HEIGHT if it has no successors
	 */
	private function setHeight ($chainNode, $deepness) {
		if (count($chainNode->getSuccessors()) == 0) {
			//executed if chainNode has no successors
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
	 * 		the - by the previous successors - already taken space and
	 * 		adding the successor's height afterwards
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
	 * calculates the node's coordinates in pixels
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
			//a pkmn icon should appear in the middle
			//		(concerning height) of its tree branch
			//without this it would be set at the top of its branch
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
?>