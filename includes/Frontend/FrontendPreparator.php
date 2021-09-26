<?php
require_once 'FrontendPkmnObj.php';
//for some reason prepending __DIR__ is a fix for require not liking relative paths
require_once __DIR__.'/../Constants.php';
use MediaWiki\MediaWikiServices;

class FrontendPreparator {
	const PKMN_MARGIN_BOTTOM = 10;
	/**
	 * sets heights and y offsets for all pkmn nodes
	 * in the end it creates a new tree structure with objects that have only the least possible data needed for the SVG elements
	 */
	public function prepareForFrontend (
		BreedingChainNode $breedingTree
	): FrontendPkmnObj {
		//this has to get done before setHeight() (icon sizes are needed for setHeight())
		$this->setIconData($breedingTree);

		$this->setHeight($breedingTree);

		//the whole tree obviously starts at the top
		$breedingTree->setTreeYOffset(0);
		$this->setOffset($breedingTree);

		$finalObjectTree = $this->buildFinalObjectTree($breedingTree);
		$finalObjectTree->svgTagHeight = $breedingTree->getTreeSectionHeight();

		return $finalObjectTree;
	}

	/**
	 * runs recursively over the object tree and sets all heights
	 * an object's height is the sum of its successors' heights or its own if it has no successors
	 */
	private function setHeight (BreedingChainNode $chainNode): int {
		if (count($chainNode->getSuccessors()) == 0) {
			//executed if chainNode has no successors
			$chainNode->setTreeSectionHeight(
				$chainNode->getHeight() + self::PKMN_MARGIN_BOTTOM
			);
			return $chainNode->getHeight() + self::PKMN_MARGIN_BOTTOM;
		}

		$height = 0;

		foreach ($chainNode->getSuccessors() as $successor) {
			$height += $this->setHeight($successor);
		}

		$chainNode->setTreeSectionHeight($height);
		return $height;
	}

	/**
	 * runs recursively over the object tree and sets all y offsets
	 * in one function call the offsets for chainNode's successors are set by 
	 * 		saving the - by the previous successors - already taken space and
	 * 		adding the successor's height afterwards
	 */
	private function setOffset (BreedingChainNode $chainNode) {
		$takenSpace = 0;

		foreach ($chainNode->getSuccessors() as $successor) {
			$offset = $chainNode->getTreeYOffset() + $takenSpace;
			$successor->setTreeYOffset($offset);
			$takenSpace += $successor->getTreeSectionHeight();
			$this->setOffset($successor);
		}
	}

	private function buildFinalObjectTree (
		BreedingChainNode $breedingTree
	) : FrontendPkmnObj {
		$result = $this->handleChainNode($breedingTree, 0);

		return $result;
	}

	/**
	 * calculates the node's coordinates in pixels
	 * creates new object for the final svg object structure
	 */
	private function handleChainNode (
		BreedingChainNode $breedingChainNode,
		int $currentDeepness
	): FrontendPkmnObj {
		$pkmnX = $currentDeepness * Constants::PKMN_MARGIN_HORI - $breedingChainNode->getIconWidth() / 2;
		$pkmnY = $breedingChainNode->getTreeYOffset();
		if ($breedingChainNode->getTreeSectionHeight() > $breedingChainNode->getIconHeight()) {
			//this is only needed for pkmn with successors
			//a pkmn icon should appear in the middle (concerning height) of its tree branch
			//without this it would be set at the top of its branch
			$pkmnY += ($breedingChainNode->getTreeSectionHeight() / 2);
		}

		$frontendPkmn = new FrontendPkmnObj(
			$breedingChainNode->getName(),
			$pkmnX,
			$pkmnY,
			$breedingChainNode->getIconUrl(),
			$breedingChainNode->getIconWidth(),
			$breedingChainNode->getIconHeight()
		);

		if ($breedingChainNode->getFileError() !== '') {
			$frontendPkmn->setFileError($breedingChainNode->getFileError());
		}
		if ($breedingChainNode->getLearnsByEvent()) {
			$frontendPkmn->setLearnsByEvent();
		}

		foreach ($breedingChainNode->getSuccessors() as $successor) {
			$successorObject = $this->handleChainNode($successor, $currentDeepness + 1);
			$frontendPkmn->addSuccessor($successorObject);
		}

		return $frontendPkmn;
	}

	private function setIconData (BreedingChainNode $pkmnObj) {
		try {
			$pkmnName = $pkmnObj->getName();
			$pkmnData = Constants::$pkmnData->$pkmnName;
			$pkmnId = $pkmnData->id;

			$fileObj = $this->getFile($pkmnId);

			$pkmnObj->setIconUrl($fileObj->getUrl());
			$pkmnObj->setIconWidth($fileObj->getWidth());
			$pkmnObj->setIconHeight($fileObj->getHeight());
		} catch (Exception $e) {
			//.' ' prevents possible problems if $e is empty
			$pkmnObj->setFileError($e.' ');
		}

		foreach ($pkmnObj->getSuccessors() as $successor) {
			$this->setIconData($successor);
		}
	}

	private function getFile (int $pkmnId): File {
		//pkmn icon files have 0 at the beginning if needed
		if ($pkmnId < 100) {
			$pkmnId = '0'.$pkmnId;
			if ($pkmnId < 10) {
				$pkmnId = '0'.$pkmnId;
			}
		}

		$fileName = 'Pokémon-Icon '.$pkmnId.'.png';
		$fileObj = MediaWikiServices::getInstance()->getRepoGroup()->findFile($fileName);

		if ($fileObj === false) {
			throw new Exception('pkmn icon not found');
		}

		return $fileObj;
	}
}