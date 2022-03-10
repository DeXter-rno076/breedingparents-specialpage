<?php
require_once __DIR__.'/../exceptions/FileNotFoundException.php';
require_once __DIR__.'/../tree_creation/BreedingTreeNode.php';
require_once __DIR__.'/../tree_creation/PkmnData.php';
require_once __DIR__.'/../Pkmn.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../Constants.php';

use MediaWiki\MediaWikiServices;

class FrontendPkmn extends Pkmn {
	private $learnsByEvent;
	private $learnsByOldGen;
	private $successors = [];
	private $isRoot;

	private $pkmnData;

	private $x;
	private $y;
	private $treeSectionHeight;

	private $iconUrl;
	private $iconWidth;
	private $iconHeight;
	private $fileError = null;

	public function __construct (BreedingTreeNode $breedingTreeNode) {
		parent::__construct($breedingTreeNode->getName(), $breedingTreeNode->getID());

		$this->learnsByEvent = $breedingTreeNode->getLearnsByEvent();
		$this->learnsByOldGen = $breedingTreeNode->getLearnsByOldGen();
		$this->isRoot = $breedingTreeNode->isRoot();
		$this->pkmnData = $breedingTreeNode->getJSONPkmnData();

		foreach ($breedingTreeNode->getSuccessors() as $successorTreeNode) {
			$successorFrontendObj = new FrontendPkmn($successorTreeNode);
			$this->addSuccessor($successorFrontendObj);
		}
	}

	public function getDepth (): int {
		$highestDepth = 0;
		foreach ($this->successors as $successor) {
			$successorDepth = $successor->getDepth();
			if ($successorDepth > $highestDepth) {
				$highestDepth = $successorDepth;
			}
		}

		Logger::statusLog('new highest depth '.$highestDepth);

		return $highestDepth + 1;
	}

	public function setTreeIconsAndCoordinates () {
		$this->setIconData();

		$this->calcTreeSectionHeights();
		$this->calcYCoords(0);

		$this->calcXCoords();
	}

	private function setIconData () {
		try {
			$this->tryLoadAndSetIconData();
		} catch (FileNotFoundException $e) {
			$this->setFileError($e);
		}

		foreach ($this->successors as $successor) {
			$successor->setIconData();
		}
	}

	private function tryLoadAndSetIconData () {
		$iconFileObj = FrontendPkmn::getPkmnIcon($this->id);
		Logger::statusLog('icon file for '.$this.' successfully loaded');

		$this->iconUrl = $iconFileObj->getUrl();
		$this->iconWidth = $iconFileObj->getWidth();
		$this->iconHeight = $iconFileObj->getHeight();
	}

	public static function getPkmnIcon (string $pkmnId): File {
		$fileName = 'Pokémon-Icon '.$pkmnId.'.png';
		$fileObj = MediaWikiServices::getInstance()->getRepoGroup()->findFile($fileName);

		if ($fileObj === false) {
			throw new FileNotFoundException($pkmnId);
		}

		return $fileObj;
	}

	private function setFileError (FileNotFoundException $e) {
		Logger::statusLog('couldnt load file obj of '.$this);
		$this->fileError = $e;
	}

	/**
	 * todo this can probably be fused with calcYCoords
	 */
	private function calcTreeSectionHeights (): int {
		if (!$this->hasSuccessors()) {
			return $this->calculateTreeSectionHeightForEndNode();
		}

		$heightSum = 0;
		foreach ($this->successors as $successor) {
			$heightSum += $this->calculateTreeSectionHeightForMiddleNode($successor);
		}

		Logger::statusLog('calculated tree section height '.$heightSum.' of '.$this);
		$this->treeSectionHeight = $heightSum;
		return $heightSum;
	}

	private function calculateTreeSectionHeightForEndNode (): int {
		$height = $this->calculateEndNodeHeight();

		Logger::statusLog($this.' has no successors => setting and returning minimal height '.$height);

		$this->treeSectionHeight = $height;

		return $height;
	}

	private function calculateEndNodeHeight (): int {
		$pureHeight = -1;
		if ($this->learnsByEvent || $this->learnsByOldGen) {
			$pureHeight = $this->calculateDiagonal();
		} else {
			$pureHeight = $this->getHeight();
		}

		return $pureHeight + 2*Constants::SVG_CIRCLE_MARGIN;
	}

	public function calculateDiagonal (): int {
		return sqrt(pow($this->getHeight(), 2) +  pow($this->getWidth(), 2));
	}

	private function calculateTreeSectionHeightForMiddleNode (FrontendPkmn $successor): int {
		$successorTreeSectionHeight = $successor->calcTreeSectionHeights();
		return $successorTreeSectionHeight;
	}

	/**
	 * todo this can be probably fused with calcTreeSectionHeights;
	 */
	private function calcYCoords (int $sectionYOffset): int {
		$this->y = $this->calculateYCoordinateOfNode($sectionYOffset);

		Logger::statusLog('calculated y '.$this->y.' of '.$this);

		$successorOffset = $sectionYOffset;
		foreach ($this->successors as $successor) {
			$successorSectionHeight = $successor->calcYCoords($successorOffset);
			$successorOffset += $successorSectionHeight;
		}

		Logger::statusLog('returning tree section height '
			.$this->treeSectionHeight.' of '.$this);
		return $this->treeSectionHeight;
	}

	private function calculateYCoordinateOfNode (int $sectionYOffset): int {
		return $sectionYOffset + ($this->treeSectionHeight - $this->getIconHeight())/2;
	}

	/**
	 * Calculates x coordinates of every node by traversing through the
	 * tree layers und counting the deepness. The margin betweent each
	 * pkmn column is saved in Constants::PKMN_MARGIN_HORI.
	 * @param int $deepness
	 */
	private function calcXCoords (int $deepness = 0) {
		$this->x = $this->calculateCenteredXCoordinate($deepness);
		Logger::statusLog('calculated x coordinate of '.$this);

		foreach ($this->successors as $successor) {
			$successor->calcXCoords($deepness + 1);
		}
	}
	
	private function calculateCenteredXCoordinate (int $deepness): int {
		$uncenteredX = $deepness * Constants::PKMN_MARGIN_HORIZONTAL;
		return $this->centerXCoordinate($uncenteredX);
	}

	private function centerXCoordinate (int $x): int {
		return $x - $this->getIconWidth() / 2; 
	}

	public function addSuccessor (FrontendPkmn $successor) {
		array_push($this->successors, $successor);
	}

	public function hasSuccessors (): bool {
		return count($this->successors) > 0;
	}

	public function getSuccessors (): array {
		return $this->successors;
	}

	public function getLearnsByEvent (): bool {
		return $this->learnsByEvent;
	}

	public function getLearnsByOldGen (): bool {
		return $this->learnsByOldGen;
	}

	public function getIconUrl (): string {
		return $this->iconUrl;
	}

	public function getIconWidth (): int {
		return $this->iconWidth;
	}

	public function getIconHeight (): int {
		return $this->iconHeight;
	}

	public function getFileError (): ?FileNotFoundException {
		return $this->fileError;
	}

	public function getTreeSectionHeight (): int {
		return $this->treeSectionHeight;
	}

	public function getX (): int {
		return $this->x;
	}

	public function getMiddleX (): int {
		$middleX = $this->getX() + $this->getWidth() / 2;
		return $middleX;
	}

	public function getIconX (): int {
		return $this->getX();
	}

	public function getY (): int {
		return $this->y;
	}

	public function getMiddleY (): int {
		$middleY = $this->getY() + $this->getHeight() / 2;
		return $middleY;
	}

	public function getWidth (): int {
		return $this->iconWidth;
	}

	public function getHeight (): int {
		return $this->iconHeight;
	}

	public function isRoot (): bool {
		return $this->isRoot;
	}

	public function getJSONPkmnData (): PkmnData {
		return $this->pkmnData;
	}

	/**
	 * @return string - FrontendPkmn:<pkmn name>;(<x>;<y>);<branch position>;;
	 */
	public function getLogInfo (): string {
		$msg = 'FrontendPkmn:\'\'\''.$this->name.'\'\'\';('
			.(isset($this->x) ? $this->x : '-').';'
			.(isset($this->y) ? $this->y : '-').')';
		if (count($this->successors) > 0) {
			$msg .= ';branch-middle';
		} else {
			$msg .= ';branch-end';
		}
		$msg .= ';;';
		return $msg;
	}
}