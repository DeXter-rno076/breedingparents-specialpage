<?php
require_once __DIR__.'/../output_messages/ErrorMessage.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';
require_once __DIR__.'/../Constants.php';

require_once 'SVGImg.php';
require_once 'SVGLine.php';
require_once 'SVGLink.php';
require_once 'SVGCircle.php';
require_once 'SVGRectangle.php';
require_once 'SVGText.php';
require_once 'FrontendPkmn.php';

class SVGPkmn {
	private ?SVGLink $pkmnLink = null;
	private array $lineConnections = [];
	private array $successors = [];
	private FrontendPkmn $nodeFrontendPkmn;

	private int $middleColumnX = 0;

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
		$pkmnIconSVG = new SVGImg($this->nodeFrontendPkmn);
		$linkSVG = new SVGLink($this->nodeFrontendPkmn->getName(), $pkmnIconSVG);
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
		
		return $this->nodeFrontendPkmn->isRoot() && $firstSuccessor->isRoot() && $firstSuccessorIsLowestEvoOfThis;
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

		$horizontalLine = new SVGLine($startX, $y, $endX, $y);
		$upperArrowPart = new SVGLine($startX, $y, $startX + 10, $y - 10);
		$lowerArrowPart = new SVGLine($startX, $y, $startX + 10, $y + 10);

		$connectionText = new SVGText(
			$startX + 30, $y - 2, Constants::$centralSpecialPageInstance->msg('breedingchains-evo'));

		return [
			$horizontalLine, $upperArrowPart, $lowerArrowPart,
			$connectionText	//todo mixing a SVGText with SVGLines is unclean af
		];
	}

	private function calculateNodeConnectionMarginLeft (FrontendPkmn $successor): int {
		return $successor->getMiddleX() - $successor->calculateDiagonal()/2 
			- Constants::SVG_CIRCLE_MARGIN + Constants::SVG_CIRCLE_LINE_WIDTH/2;
	}

	private function addConnections (array $connections) {
		foreach ($connections as $connection) {
			array_push($this->lineConnections, $connection);
		}
	}

	private function setMiddleColumnX () {
		$this->middleColumnX = $this->nodeFrontendPkmn->getMiddleX() + Constants::PKMN_MARGIN_HORIZONTAL / 1.5;
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

	private function createPkmnMiddleConnection (): SVGLine {
		$startX = $this->calculateNodeConnectionMarginRight();
		$startY = $this->nodeFrontendPkmn->getMiddleY();
		$horizontalLine = new SVGLine(
			$startX, $startY,
			$this->middleColumnX, $startY);
		
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

	private function createMiddleLine (): SVGLine {
		$lowestY = $this->getLowestYCoordinateFromTreeSection();
		$highestY = $this->getHighestYCoordinateFromTreeSection();

		if (count($this->nodeFrontendPkmn->getSuccessors()) === 1) {
			$this->adjustCoordinatesToOnlyOneSuccessor($lowestY, $highestY);
		}

		$verticalLine = new SVGLine(
			$this->middleColumnX, $lowestY,
			$this->middleColumnX, $highestY);
		
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

	private function createMiddleToSuccessorConnection (FrontendPkmn $successor): SVGLine {
		$startX = $this->middleColumnX - Constants::SVG_CIRCLE_LINE_WIDTH/2;
		$endX = $this->calculateNodeConnectionMarginLeft($successor);
		$y = $successor->getMiddleY();

		$line = new SVGLine($startX, $y, $endX, $y);
		return $line;
	}

	public function toHTML (int $xOffset, int $yOffset): array {
		$tagArray = [];

		$htmlTagCreationOptions = [
			'tagArray' => &$tagArray,
			'xOffset' => $xOffset,
			'yOffset' => $yOffset
		];

		$this->addIconOrFileErrorToHTMLTagArray($htmlTagCreationOptions);
		$this->addSpecialLearnsetMarkerToHTMLTagArray($htmlTagCreationOptions);
		$this->addLineConnectionsToHTMLTagArray($htmlTagCreationOptions);
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

	private function addSpecialLearnsetMarkerToHTMLTagArray (array &$htmlTagCreationOptions) {
		if ($this->nodeFrontendPkmn->getLearnsByOldGen()) {
			Logger::statusLog('adding old gen marker to '.$this);
			array_push(
				$htmlTagCreationOptions['tagArray'],
				$this->createOldGenMarker($htmlTagCreationOptions['xOffset'], $htmlTagCreationOptions['yOffset'])
			);
		} else if ($this->nodeFrontendPkmn->getLearnsByEvent()) {
			Logger::statusLog('adding event marker to '.$this);
			array_push(
				$htmlTagCreationOptions['tagArray'],
				$this->createEventMarker($htmlTagCreationOptions['xOffset'], $htmlTagCreationOptions['yOffset'])
			);
		}
	}

	private function createOldGenMarker (int $xOffset, int $yOffset): HTMLElement {
		$middleX = $this->nodeFrontendPkmn->getMiddleX();
		$middleY = $this->nodeFrontendPkmn->getMiddleY();
		$radius = $this->calculateOldGenMarkerRadius();

		$oldGenMarker = new SVGCircle($middleX, $middleY, $radius);

		return $oldGenMarker->toHTML($xOffset, $yOffset);
	}

	private function calculateOldGenMarkerRadius (): int {
		$diagonal = $this->nodeFrontendPkmn->calculateDiagonal();
		$distanceFromMiddleToCorners = $diagonal / 2;

		return $distanceFromMiddleToCorners + Constants::SVG_CIRCLE_MARGIN;
	}

	private function createEventMarker (int $xOffset, int $yOffset): HTMLElement {
		$x = $this->nodeFrontendPkmn->getX();
		$y = $this->nodeFrontendPkmn->getY();
		$width = $this->nodeFrontendPkmn->getWidth();
		$height = $this->nodeFrontendPkmn->getHeight();

		$eventMarker = new SVGRectangle(
			$x - Constants::SVG_RECTANGLE_PADDING,
			$y - Constants::SVG_RECTANGLE_PADDING,
			$width + 2 * Constants::SVG_RECTANGLE_PADDING,
			$height + 2 * Constants::SVG_RECTANGLE_PADDING
		);

		return $eventMarker->toHTML($xOffset, $yOffset);
	}

	private function addLineConnectionsToHTMLTagArray (array &$htmlTagCreationOptions) {
		foreach ($this->lineConnections as $line) {
			array_push(
				$htmlTagCreationOptions['tagArray'],
				$line->toHTML($htmlTagCreationOptions['xOffset'], $htmlTagCreationOptions['yOffset'])
			);
		}
	}

	private function addSuccessorsToHTMLTagArray (array &$htmlTagCreationOptions) {
		foreach ($this->successors as $successor) {
			$this->addSuccessorTagsToHTMLTagArray($htmlTagCreationOptions, $successor);
		}
	}

	private function addSuccessorTagsToHTMLTagArray (array &$htmlTagCreationOptions, SVGPkmn $successor) {
		$successorHTMLTags = $successor->toHTML(
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