<?php
require_once __DIR__.'/../output_messages/ErrorMessage.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';
require_once __DIR__.'/../Constants.php';

require_once 'SVGImg.php';
require_once 'SVGLink.php';
require_once 'SVGCircle.php';
require_once 'SVGRectangle.php';
require_once 'SVGText.php';
require_once 'FrontendPkmn.php';
require_once 'SVGPkmnConnection.php';

class SVGPkmn {
	private $pkmnLink = null;
	private $pkmnConnections = [];
	private $successors = [];
	private $nodeFrontendPkmn;

	private $middleColumnX = 0;

	public function __construct (FrontendPkmn $pkmn) {
		$this->nodeFrontendPkmn = $pkmn;
		$this->addIcon();
		$this->addConnectionStructure();

		foreach ($this->nodeFrontendPkmn->getSuccessors() as $successor) {
			$svgSuccessor = new SVGPkmn($successor);
			array_push($this->successors, $svgSuccessor);
		}
	}

	private function addIcon () {
		$fileError = $this->nodeFrontendPkmn->getFileError();
		if ($this->fileWasLoaded($fileError)) {
			$this->setPkmnIcon();
		}
	}

	private function fileWasLoaded (?FileNotFoundException $fileError): bool {
		return is_null($fileError);
	}

	private function setPkmnIcon () {
		Logger::statusLog($this->nodeFrontendPkmn.' has no file error set => can add icon');
		$pkmnIconSVG = new SVGImg($this->nodeFrontendPkmn, $this->nodeFrontendPkmn->getGroupId());
		$linkSVG = new SVGLink(
			$this->nodeFrontendPkmn->getName(),
			$pkmnIconSVG,
			$this->nodeFrontendPkmn->getGroupId()
		);
		$this->pkmnLink = $linkSVG;
	}

	private function addConnectionStructure () {
		Logger::statusLog('adding connection structure after '.$this);
		$nodeFrontendPkmn = $this->nodeFrontendPkmn;

		if (!$nodeFrontendPkmn->hasSuccessors()) {
			Logger::statusLog($nodeFrontendPkmn.' has no successors => not adding any lines');
		} else if ($this->hasEvoConnection()) {
			$this->addEvoConnection();
		} else {
			Logger::statusLog($this.' has no evo connection -> adding standard line structure');
			$this->setMiddleColumnX();
			$this->addLeftHalfConnectionLines();
			$this->addMiddleToSuccessorConnections();
		}
	}

	private function hasEvoConnection (): bool {
		$firstSuccessor = $this->nodeFrontendPkmn->getSuccessors()[0];
		$firstSuccessorJSONData = $firstSuccessor->getJSONPkmnData();

		$firstSuccessorIsLowestEvoOfThis = $firstSuccessorJSONData->isLowestEvolution() 
			&& $firstSuccessorJSONData->hasAsEvolution($this->nodeFrontendPkmn->getName());
		
		return $this->nodeFrontendPkmn->isRoot() 
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
		$evoFrontendPkmnInstance = $this->nodeFrontendPkmn->getSuccessors()[0];

		$startX = $this->calculateNodeConnectionMarginRight();
		$endX = $this->calculateNodeConnectionMarginLeft($evoFrontendPkmnInstance);
		$y = $this->nodeFrontendPkmn->getMiddleY();

		$horizontalLine = new SVGLine($startX, $y, $endX, $y, $this->nodeFrontendPkmn->getGroupId());
		$upperArrowPart = SVGPkmnConnection::constructWithoutText(
			$startX, $y, $startX + 10, $y - 10, $this->nodeFrontendPkmn->getGroupId());
		$lowerArrowPart = SVGPkmnConnection::constructWithoutText(
			$startX, $y, $startX + 10, $y + 10, $this->nodeFrontendPkmn->getGroupId());

		$horizontalConnection = new SVGPkmnConnection(
			$horizontalLine, $this->nodeFrontendPkmn->getGroupId(), Constants::i18nMsg('breedingchains-evo'));

		return [
			$horizontalConnection, $upperArrowPart, $lowerArrowPart
		];
	}

	private function calculateNodeConnectionMarginLeft (FrontendPkmn $successor): int {
		return $successor->getMiddleX() - $successor->calculateDiagonal()/2 
			- Constants::SVG_CIRCLE_MARGIN;// + Constants::SVG_LINE_WIDTH/2;
	}

	private function addConnections (array $connections) {
		foreach ($connections as $connection) {
			array_push($this->pkmnConnections, $connection);
		}
	}

	private function setMiddleColumnX () {
		$this->middleColumnX = $this->nodeFrontendPkmn->getMiddleX() + Constants::PKMN_MARGIN_HORIZONTAL / 3;
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
		$startY = $this->nodeFrontendPkmn->getMiddleY();
		$horizontalLine = SVGPkmnConnection::constructWithoutText(
			$startX, $startY,
			$this->middleColumnX, $startY, $this->nodeFrontendPkmn->getGroupId());
		
		return $horizontalLine;
	}

	private function calculateNodeConnectionMarginRight (): int {
		return $this->nodeFrontendPkmn->getMiddleX() + $this->nodeFrontendPkmn->calculateDiagonal()/2 
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

		if (count($this->nodeFrontendPkmn->getSuccessors()) === 1) {
			$this->adjustCoordinatesToOnlyOneSuccessor($lowestY, $highestY);
		}

		$verticalLine = SVGPkmnConnection::constructWithoutText(
			$this->middleColumnX, $lowestY,
			$this->middleColumnX, $highestY, Constants::UNUSED_GROUP_ID);
		
		return $verticalLine;
	}

	private function getLowestYCoordinateFromTreeSection (): int {
		$successors = $this->nodeFrontendPkmn->getSuccessors();
		$firstSuccessor = $successors[0];
		return $firstSuccessor->getMiddleY();
	}

	private function getHighestYCoordinateFromTreeSection (): int {
		$successors = $this->nodeFrontendPkmn->getSuccessors();
		$lastSuccessor = $successors[count($successors) - 1];
		return $lastSuccessor->getMiddleY();
	}

	private function adjustCoordinatesToOnlyOneSuccessor (int &$lowestY, int &$highestY) {
		Logger::statusLog('adjusting coordinates to only one successor');
		$successor = $this->nodeFrontendPkmn->getSuccessors()[0];

		$nodeMiddleY = $this->nodeFrontendPkmn->getMiddleY();
		$successorMiddleY = $successor->getMiddleY();

		$lowestY = min($nodeMiddleY, $successorMiddleY);
		$highestY = max($nodeMiddleY, $successorMiddleY);
	}

	private function addMiddleToSuccessorConnections () {
		foreach ($this->nodeFrontendPkmn->getSuccessors() as $successor) {
			$this->addMiddleToSuccessorConnection($successor);
		}
	}

	private function addMiddleToSuccessorConnection (FrontendPkmn $successor) {
		Logger::statusLog('adding connection from middle to '.$successor);
		$middleToSuccessorConnection = $this->createMiddleToSuccessorConnection($successor);
		$this->addConnections([$middleToSuccessorConnection]);
	}

	private function createMiddleToSuccessorConnection (FrontendPkmn $successor): SVGPkmnConnection {
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
		$this->addPkmnConnectionsToHTMLTagArray($htmlTagCreationOptions);
		$this->addSuccessorsToHTMLTagArray($htmlTagCreationOptions);

		return $tagArray;
	}

	private function addIconOrFileErrorToHTMLTagArray (array &$htmlTagCreationOptions) {
		if (!is_null($this->pkmnLink)) {
			array_push(
				$htmlTagCreationOptions['tagArray'],
				$this->pkmnLink->toHTML($htmlTagCreationOptions['xOffset'], $htmlTagCreationOptions['yOffset'])
			);
		} else {
			$errorMessage = new ErrorMessage($this->nodeFrontendPkmn->getFileError());
			$errorMessage->output();
		}
	}

	private function addCircle (array &$htmlTagCreationOptions) {
		$circleColor = '#3388ff';

		if ($this->nodeFrontendPkmn->getLearnsByOldGen()) {
			$circleColor = 'yellow';
		} else if ($this->nodeFrontendPkmn->getLearnsByEvent()) {
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
		$middleX = $this->nodeFrontendPkmn->getMiddleX();
		$middleY = $this->nodeFrontendPkmn->getMiddleY();
		$radius = $this->calculateOldGenMarkerRadius();

		$oldGenMarker = new SVGCircle(
			$middleX, $middleY, $radius, $color, $this->nodeFrontendPkmn->getGroupId());

		return $oldGenMarker->toHTML($xOffset, $yOffset);
	}

	private function calculateOldGenMarkerRadius (): int {
		$diagonal = $this->nodeFrontendPkmn->calculateDiagonal();
		$distanceFromMiddleToCorners = $diagonal / 2;

		return $distanceFromMiddleToCorners + Constants::SVG_CIRCLE_MARGIN;
	}

	private function addPkmnConnectionsToHTMLTagArray (array &$htmlTagCreationOptions) {
		foreach ($this->pkmnConnections as $connection) {
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

	private function addSuccessorTagsToHTMLTagArray (array &$htmlTagCreationOptions, SVGPkmn $successor) {
		$successorHTMLTags = $successor->toHTMLElements(
			$htmlTagCreationOptions['xOffset'],
			$htmlTagCreationOptions['yOffset']
		);
		foreach ($successorHTMLTags as $tag) {
			array_push($htmlTagCreationOptions['tagArray'], $tag);
		}
	}

	/**
	 * @return string SVGPkmn:<frontendPkmn instance>
	 */
	public function getLogInfo (): string {
		return 'SVGPkmn:'.$this->nodeFrontendPkmn;
	}

	public function __toString (): string {
		return $this->getLogInfo();
	}
}