<?php
require_once __DIR__.'/../exceptions/FileNotFoundException.php';
require_once __DIR__.'/../tree_creation/BreedingTreeNode.php';
require_once __DIR__.'/../tree_creation/PkmnData.php';
require_once __DIR__.'/../tree_creation/PkmnTreeRoot.php';
require_once __DIR__.'/../Pkmn.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../Constants.php';

use MediaWiki\MediaWikiServices;

class VisualNode extends Pkmn {
	private $displayEventMarker;
	private $displayOldGenMarker;
	private $successors = [];
	private $isRoot;

	private $x;
	private $y;
	private $treeSectionHeight;

	private $iconName;
	private $iconUrl = '';
	private $iconWidth = 0;
	private $iconHeight = 0;
	private $fileError = null;

	private $groupId;

	public function __construct (BreedingTreeNode $breedingTreeNode) {
		parent::__construct($breedingTreeNode->getName());

		$this->displayEventMarker = $breedingTreeNode->getLearnabilityStatus()->getLearnsByEvent();
		$this->displayOldGenMarker = $breedingTreeNode->getLearnabilityStatus()->getLearnsByOldGen();
		$this->isRoot = $breedingTreeNode instanceof PkmnTreeRoot;
		$this->groupId = Constants::generateGroupId();
		$this->iconName = $breedingTreeNode->buildIconName();

		foreach ($breedingTreeNode->getSuccessors() as $successorTreeNode) {
			$successorFrontendObj = new VisualNode($successorTreeNode);
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

	public function prep () {
		$this->setIconData();

		$this->calcTreeSectionHeights();
		$this->orderSuccessors();

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
		$iconFileObj = VisualNode::getIcon($this->iconName);
		Logger::statusLog('icon file for '.$this.' successfully loaded');

		$this->iconUrl = $iconFileObj->getUrl();
		$this->iconWidth = $iconFileObj->getWidth();
		$this->iconHeight = $iconFileObj->getHeight();
	}

	public static function getIcon (string $fileURL): File {
		$fileObj = MediaWikiServices::getInstance()->getRepoGroup()->findFile($fileURL);

		if ($fileObj === false) {
			throw new FileNotFoundException($fileURL);
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
		$pureHeight = $this->calculateDiagonal() + 10;

		return $pureHeight + 2*Constants::SVG_CIRCLE_MARGIN;
	}

	public function calculateDiagonal (): int {
		return Constants::SVG_CIRCLE_DIAMETER;
	}

	private function calculateTreeSectionHeightForMiddleNode (VisualNode $successor): int {
		$successorTreeSectionHeight = $successor->calcTreeSectionHeights();
		return $successorTreeSectionHeight;
	}

	private function orderSuccessors () {
		if (count($this->successors) > 1) {
			Logger::statusLog('ordering succesors of '.$this);
			$this->sortSuccessors();
			$this->changeOrderToFromMiddleToOuterLayers();
		} else {
			Logger::statusLog('not ordering succesors of '.$this.', less then 2 successors -> unnecessary');
		}

		foreach ($this->successors as $successor) {
			$successor->orderSuccessors();
		}
	}

	private function sortSuccessors () {
		usort($this->successors, function ($first, $second) {
			return $first->treeSectionHeight <=> $second->treeSectionHeight;
		});
		Logger::statusLog('successor array of '.$this.' with treeSectionHeightKeys after pure sorting: '
			.json_encode($this->successors));
	}

	/**
	 * todo explain this
	 * short draft: the smaller the sub tree, the closer to the middle
	 * sortSuccessors sorts all successors descending
	 * changeOrderToFromMiddleToOuterLayern starts from the end and jumps 2 indices at a time to the start
	 * makes a u turn, steps one index further and hops 2 indices at a time to the end
	 * by pushing a successor at every jump to a list, the successors are reodered as wanted
	 */
	private function changeOrderToFromMiddleToOuterLayers () {
		Logger::statusLog('changing sorting order for '.$this.', '.count($this->successors).' successors');
		$middleToOuterOrdering = [];
		$pointer = $this->getSuccessorOrderingBackPointer();
		Logger::statusLog('starting pointer from back: '.$pointer);

		for (; $pointer >= 0; $pointer -= 2) {
			Logger::statusLog('new pointer: '.$pointer);
			$middleToOuterOrdering[] = $this->successors[$pointer];
		}
		$pointer += 2;
		Logger::statusLog('corrected pointer to '.$pointer.' after first run');

		for ($pointer++ ; $pointer < count($this->successors); $pointer += 2) {
			Logger::statusLog('new pointer: '.$pointer);
			$middleToOuterOrdering[] = $this->successors[$pointer];
		}

		$this->successors = $middleToOuterOrdering;
	}

	private function getSuccessorOrderingBackPointer (): int {
		$successorsAmount = count($this->successors);
		$secondLastSuccessorIndex = $successorsAmount - 2;
		$lastSuccessorIndex = $successorsAmount - 1;

		if ($successorsAmount % 2 === 0) {
			return $secondLastSuccessorIndex;
		} else {
			return $lastSuccessorIndex;
		}
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

	public function addSuccessor (VisualNode $successor) {
		array_push($this->successors, $successor);
	}

	public function hasSuccessors (): bool {
		return count($this->successors) > 0;
	}

	public function getSuccessors (): array {
		return $this->successors;
	}

	public function getDisplayEventMarker (): bool {
		return $this->displayEventMarker;
	}

	public function getDisplayOldGenMarker (): bool {
		return $this->displayOldGenMarker;
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

	public function getGroupId (): int {
		return $this->groupId;
	}

	public function getFirstPkmnSuccessor (): ?VisualNode {
		foreach ($this->successors as $successor) {
			if (Constants::isPkmn($successor->getName())) {
				return $successor;
			}
		}
		return null;
	}

	public function getArticleLink (): string {
		if (Constants::isPkmn($this->getName())) {
			try {
				$pkmnData = new PkmnData($this->getName());
				$linkSuperPage = $pkmnData->getArticleLinkSuperPageName();
				$linkName = Constants::i18nMsg('breedingchains-learnsetpage-link',
					$linkSuperPage, Constants::$targetGenNumber);
				return $linkName;
			} catch (Exception $e) {
				$eMessage = new ErrorMessage($e);
				$eMessage->output();
			}
		} else {
			return $this->getName();
		}
	}

	/**
	 * @return string - VisualNode:<pkmn name>;(<x>;<y>);<branch position>;;
	 */
	public function getLogInfo (): string {
		$msg = 'VisualNode:\'\'\''.$this->name.'\'\'\';('
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