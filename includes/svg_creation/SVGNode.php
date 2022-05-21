<?php
require_once __DIR__.'/../output_messages/ErrorMessage.php';
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';
require_once __DIR__.'/../Constants.php';

require_once 'SVGImg.php';
require_once 'SVGLink.php';
require_once 'SVGCircle.php';
require_once 'SVGRectangle.php';
require_once 'SVGText.php';
require_once 'VisualNode.php';
require_once 'SVGPkmnConnection.php';

class SVGNode {
	private $nodeLink = null;
	private $nodeConnections = [];
	private $successors = [];
	private $visualNode;

	private $middleColumnX = 0;

	public function __construct (VisualNode $visualNode) {
		$this->visualNode = $visualNode;
		$this->addIcon();
		$this->addConnectionStructure();

		foreach ($this->visualNode->getSuccessors() as $successor) {
			try {
				$svgSuccessor = new SVGNode($successor);
				array_push($this->successors, $svgSuccessor);
			} catch (AttributeNotFoundException $e) {
				Logger::elog($e->__toString());
			}
		}
	}

	private function addIcon () {
		$fileError = $this->visualNode->getFileError();
		if ($this->fileWasLoaded($fileError)) {
			$this->setNodeIcon();
		}
	}

	private function fileWasLoaded (?FileNotFoundException $fileError): bool {
		return is_null($fileError);
	}

	private function setNodeIcon () {
		Logger::statusLog($this->visualNode.' has no file error set => can add icon');
		$pkmnIconSVG = new SVGImg($this->visualNode, $this->visualNode->getGroupId());
		$linkSVG = new SVGLink(
			$this->visualNode,
			$pkmnIconSVG,
			$this->visualNode->getGroupId()
		);
		$this->nodeLink = $linkSVG;
	}

	private function addConnectionStructure () {
		Logger::statusLog('adding connection structure after '.$this);
		$visualNode = $this->visualNode;

		if (!$visualNode->hasSuccessors()) {
			Logger::statusLog($visualNode.' has no successors => not adding any lines');
		} else if ($this->hasEvoConnection()) {
			$this->addEvoConnection();
		} else {
			Logger::statusLog($this.' has no evo connection -> adding standard line structure');
			$this->setMiddleColumnX();
			$this->addLeftHalfConnectionLines();
			$this->addMiddleToSuccessorConnections();
		}
	}

	/**
	 * todo find a cleaner implementation than getFirstPkmnSuccessor
	 * todo evoConnection methods are a connection to Pokemon
	 * @throws AttributeNotFoundException
	 */
	private function hasEvoConnection (): bool {
		$firstSuccessor = $this->visualNode->getFirstPkmnSuccessor();
		if (is_null($firstSuccessor)) {
			return false;
		}
		$firstSuccessorJSONData = new PkmnData($firstSuccessor->getName());

		$firstSuccessorIsLowestEvoOfThis = $firstSuccessorJSONData->isLowestEvolution() 
			&& $firstSuccessorJSONData->hasAsEvolution($this->visualNode->getName());
		
		return $this->visualNode->isRoot() 
			&& $firstSuccessor->isRoot() && $firstSuccessorIsLowestEvoOfThis;
	}

	/**
	 * If the root is an evolution, the connection to its lowest evo is an
	 * arrow and gets a text to additionally show that this is an evo connection. 
	 */
	private function addEvoConnection () {
		Logger::statusLog('adding evo arrow line connection for '.$this);

		$connectionParts = $this->createEvoConnectionParts();
		$this->addConnections($connectionParts);
	}

	private function createEvoConnectionParts (): array {
		$evoFrontendPkmnInstance = $this->visualNode->getFirstPkmnSuccessor();

		$startX = $this->calculateNodeConnectionMarginRight();
		$endX = $this->calculateNodeConnectionMarginLeft($evoFrontendPkmnInstance);
		$y = $this->visualNode->getMiddleY();

		$horizontalLine = new SVGLine($startX, $y, $endX, $y, $this->visualNode->getGroupId());
		$upperArrowPart = SVGPkmnConnection::constructWithoutText(
			$startX, $y, $startX + 10, $y - 10, $this->visualNode->getGroupId());
		$lowerArrowPart = SVGPkmnConnection::constructWithoutText(
			$startX, $y, $startX + 10, $y + 10, $this->visualNode->getGroupId());

		$horizontalConnection = new SVGPkmnConnection(
			$horizontalLine, $this->visualNode->getGroupId(), Constants::i18nMsg('breedingchains-evo'));

		return [
			$horizontalConnection, $upperArrowPart, $lowerArrowPart
		];
	}

	private function calculateNodeConnectionMarginLeft (VisualNode $successor): int {
		return $successor->getMiddleX() - $successor->calculateDiagonal()/2 
			- Constants::SVG_CIRCLE_MARGIN;// + Constants::SVG_LINE_WIDTH/2;
	}

	private function addConnections (array $connections) {
		foreach ($connections as $connection) {
			array_push($this->nodeConnections, $connection);
		}
	}

	private function setMiddleColumnX () {
		$this->middleColumnX = $this->visualNode->getMiddleX() + Constants::PKMN_MARGIN_HORIZONTAL / 3;
	}

	private function addLeftHalfConnectionLines () {
		$this->addPkmnMiddleConnection();
		$this->addMiddleLine();
	}

	private function addPkmnMiddleConnection () {
		Logger::statusLog('adding line from '.$this.' middle line');
		$horizontalLine = $this->createPkmnMiddleConnection();
		$this->addConnections([$horizontalLine]);
	}

	private function createPkmnMiddleConnection (): SVGPkmnConnection {
		$startX = $this->calculateNodeConnectionMarginRight();
		$startY = $this->visualNode->getMiddleY();
		$horizontalLine = SVGPkmnConnection::constructWithoutText(
			$startX, $startY,
			$this->middleColumnX, $startY, $this->visualNode->getGroupId());
		
		return $horizontalLine;
	}

	private function calculateNodeConnectionMarginRight (): int {
		return $this->visualNode->getMiddleX() + $this->visualNode->calculateDiagonal()/2 
			+ Constants::SVG_CIRCLE_MARGIN;
	}

	private function addMiddleLine () {
		Logger::statusLog('adding middle line after '.$this);
		$middleLine = $this->createMiddleLine();
		$this->addConnections([$middleLine]);
	}

	private function createMiddleLine (): SVGPkmnConnection {
		$lowestY = $this->getLowestYCoordinateFromTreeSection();
		$highestY = $this->getHighestYCoordinateFromTreeSection();

		if (count($this->visualNode->getSuccessors()) === 1) {
			$this->adjustCoordinatesToOnlyOneSuccessor($lowestY, $highestY);
		}

		$verticalLine = SVGPkmnConnection::constructWithoutText(
			$this->middleColumnX, $lowestY,
			$this->middleColumnX, $highestY, Constants::UNUSED_GROUP_ID);
		
		return $verticalLine;
	}

	private function getLowestYCoordinateFromTreeSection (): int {
		$successors = $this->visualNode->getSuccessors();
		$firstSuccessor = $successors[0];
		return $firstSuccessor->getMiddleY();
	}

	private function getHighestYCoordinateFromTreeSection (): int {
		$successors = $this->visualNode->getSuccessors();
		$lastSuccessor = $successors[count($successors) - 1];
		return $lastSuccessor->getMiddleY();
	}

	private function adjustCoordinatesToOnlyOneSuccessor (int &$lowestY, int &$highestY) {
		Logger::statusLog('adjusting coordinates to only one successor');
		$successor = $this->visualNode->getSuccessors()[0];

		$nodeMiddleY = $this->visualNode->getMiddleY();
		$successorMiddleY = $successor->getMiddleY();

		$lowestY = min($nodeMiddleY, $successorMiddleY);
		$highestY = max($nodeMiddleY, $successorMiddleY);
	}

	private function addMiddleToSuccessorConnections () {
		foreach ($this->visualNode->getSuccessors() as $successor) {
			$this->addMiddleToSuccessorConnection($successor);
		}
	}

	private function addMiddleToSuccessorConnection (VisualNode $successor) {
		Logger::statusLog('adding connection from middle to '.$successor);
		$middleToSuccessorConnection = $this->createMiddleToSuccessorConnection($successor);
		$this->addConnections([$middleToSuccessorConnection]);
	}

	private function createMiddleToSuccessorConnection (VisualNode $successor): SVGPkmnConnection {
		$startX = $this->middleColumnX;// - Constants::SVG_LINE_WIDTH/2;
		$endX = $this->calculateNodeConnectionMarginLeft($successor);
		$y = $successor->getMiddleY();

		$line = SVGPkmnConnection::constructWithoutText($startX, $y, $endX, $y, $successor->getGroupId());
		return $line;
	}

	public function toHTMLElements (int $xOffset, int $yOffset): array {
		$tagArray = [];

		$htmlTagCreationOptions = [
			'tagArray' => &$tagArray,
			'xOffset' => $xOffset,
			'yOffset' => $yOffset
		];

		$this->addCircle($htmlTagCreationOptions);
		$this->addIconOrFileErrorToHTMLTagArray($htmlTagCreationOptions);
		$this->addnodeConnectionsToHTMLTagArray($htmlTagCreationOptions);
		$this->addSuccessorsToHTMLTagArray($htmlTagCreationOptions);

		return $tagArray;
	}

	private function addIconOrFileErrorToHTMLTagArray (array &$htmlTagCreationOptions) {
		if (!is_null($this->nodeLink)) {
			array_push(
				$htmlTagCreationOptions['tagArray'],
				$this->nodeLink->toHTML($htmlTagCreationOptions['xOffset'], $htmlTagCreationOptions['yOffset'])
			);
		} else {
			$errorMessage = new ErrorMessage($this->visualNode->getFileError());
			$errorMessage->output();
		}
	}

	private function addCircle (array &$htmlTagCreationOptions) {
		$circleColor = '#3388ff';

		if ($this->visualNode->getDisplayOldGenMarker()) {
			$circleColor = 'yellow';
		} else if ($this->visualNode->getDisplayEventMarker()) {
			$circleColor = 'green';
		}

		$circle = $this->createCircle(
			$htmlTagCreationOptions['xOffset'],
			$htmlTagCreationOptions['yOffset'],
			$circleColor
		);

		array_push(
			$htmlTagCreationOptions['tagArray'],
			$circle
		);
	}

	private function createCircle (int $xOffset, int $yOffset, string $color): HTMLElement {
		$middleX = $this->visualNode->getMiddleX();
		$middleY = $this->visualNode->getMiddleY();
		$radius = $this->calculateOldGenMarkerRadius();

		$oldGenMarker = new SVGCircle(
			$middleX, $middleY, $radius, $color, $this->visualNode->getGroupId());

		return $oldGenMarker->toHTML($xOffset, $yOffset);
	}

	private function calculateOldGenMarkerRadius (): int {
		$diagonal = $this->visualNode->calculateDiagonal();
		$distanceFromMiddleToCorners = $diagonal / 2;

		return $distanceFromMiddleToCorners + Constants::SVG_CIRCLE_MARGIN;
	}

	private function addnodeConnectionsToHTMLTagArray (array &$htmlTagCreationOptions) {
		foreach ($this->nodeConnections as $connection) {
			$connectionParts = $connection->toHTMLElements(
				$htmlTagCreationOptions['xOffset'],
				$htmlTagCreationOptions['yOffset']
			);
			foreach ($connectionParts as $part) {
				array_push(
					$htmlTagCreationOptions['tagArray'],
					$part
				);
			}
		}
	}

	private function addSuccessorsToHTMLTagArray (array &$htmlTagCreationOptions) {
		foreach ($this->successors as $successor) {
			$this->addSuccessorTagsToHTMLTagArray($htmlTagCreationOptions, $successor);
		}
	}

	private function addSuccessorTagsToHTMLTagArray (array &$htmlTagCreationOptions, SVGNode $successor) {
		$successorHTMLTags = $successor->toHTMLElements(
			$htmlTagCreationOptions['xOffset'],
			$htmlTagCreationOptions['yOffset']
		);
		foreach ($successorHTMLTags as $tag) {
			array_push($htmlTagCreationOptions['tagArray'], $tag);
		}
	}

	/**
	 * @return string SVGNode:<VisualNode instance>
	 */
	public function getLogInfo (): string {
		return 'SVGNode:'.$this->visualNode;
	}

	public function __toString (): string {
		return $this->getLogInfo();
	}
}