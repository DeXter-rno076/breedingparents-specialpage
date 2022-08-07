<?php
require_once __DIR__.'/../output_messages/ErrorMessage.php';
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';
require_once __DIR__.'/../exceptions/FileNotFoundException.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';
require_once __DIR__.'/../Constants.php';

require_once 'SVGImg.php';
require_once 'SVGLine.php';
require_once 'SVGLink.php';
require_once 'SVGCircle.php';
require_once 'SVGRectangle.php';
require_once 'SVGText.php';
require_once 'VisualNode.php';
require_once 'SVGPkmnConnection.php';
require_once 'VisualSubtree.php';

class SVGSubtree {
	private $nodeLinks = [];
	private $nodeConnections = [];
	private $successors = [];
    private $visualRoots;

	private $middleColumnX = 0;

	public function __construct (VisualSubtree $visualSubtree) {
        $this->visualRoots = $visualSubtree->getRoots();
		$this->addIcons($visualSubtree->getRoots());
		$this->addConnectionStructure($visualSubtree);

		foreach ($visualSubtree->getSuccessors() as $successor) {
			try {
				$svgSuccessor = new SVGSubtree($successor);
				array_push($this->successors, $svgSuccessor);
			} catch (AttributeNotFoundException $e) {
				Logger::elog($e->__toString());
			}
		}
	}

    private function addIcons (array $roots) {
        foreach ($roots as $root) {
            $this->addIcon($root);
        }
    }

	private function addIcon (VisualNode $visualRoot) {
		$fileError = $visualRoot->getFileError();
		if ($this->fileWasLoaded($fileError)) {
			$this->nodeLinks[] = $this->createNodeIcon($visualRoot);
		} else {
            Logger::elog($fileError->__toString());
        }
	}

	private function fileWasLoaded (?FileNotFoundException $fileError): bool {
		return is_null($fileError);
	}

	private function createNodeIcon (VisualNode $node): SVGLink {
		Logger::statusLog($node.' has no file error set => can add icon');
		$pkmnIconSVG = new SVGImg($node, $node->getGroupId());
		$linkSVG = new SVGLink(
			$node,
			$pkmnIconSVG,
			$node->getGroupId()
		);
		return $linkSVG;
	}

	private function addConnectionStructure (VisualSubtree $visualSubtree) {
		Logger::statusLog('adding connection structure after '.$this);

		if (!$visualSubtree->hasSuccessors()) {
			logger::statuslog($visualSubtree.' has no successors => not adding any lines');
		} else if ($visualSubtree->hasEvoConnection()) {
            if (count($visualSubtree->getRoots()) > 1 || count($visualSubtree->getSuccessors()) > 1) {
                Logger::elog($visualSubtree.' has multiple roots or successors even though it has an evo connection');
                return;
            }
			$this->addEvoConnection($visualSubtree);
		} else {
			Logger::statusLog($this.' has no evo connection -> adding standard line structure');
			$this->middleColumnX = $this->calcMiddleColumnX($visualSubtree);
			$this->addLeftHalfConnectionLines($visualSubtree);
			$this->addMiddleToSuccessorConnections($visualSubtree);
		}
	}	

	/**
	 * if the root is an evolution, the connection to its lowest evo is an
	 * arrow and gets a text to additionally show that this is an evo connection. 
	 */
	private function addEvoconnection (VisualSubtree $visualSubtree) {
		Logger::statusLog('adding evo arrow line connection for '.$this);

		$connectionParts = $this->createEvoconnectionParts($visualSubtree);
		$this->addConnections($connectionParts);
	}

	private function createEvoConnectionParts (VisualSubtree $visualSubtree): array {
		$visualRoot = $visualSubtree->getRoots()[0];
        if (is_null($visualRoot)) {
            Logger::elog('unexpected empty roots array of '.$visualRoot);
            return [];
        }
        $evoFrontendPkmnInstance = $visualSubtree->getFirstPkmnSuccessor();

		$startX = $this->calculateNodeConnectionMarginRight($visualRoot);
		$endX = $this->calculateNodeConnectionMarginLeft($evoFrontendPkmnInstance);
		$y = $visualRoot->getMiddleY();

		$horizontalLine = new SVGLine($startX, $y, $endX, $y, $visualRoot->getGroupId());
		$upperArrowPart = SVGPkmnConnection::constructWithoutText(
			$startX, $y, $startX + 10, $y - 10, $visualRoot->getGroupId());
		$lowerArrowPart = SVGPkmnConnection::constructWithoutText(
			$startX, $y, $startX + 10, $y + 10, $visualRoot->getGroupId());

		$horizontalConnection = new SVGPkmnConnection(
			$horizontalLine, $visualRoot->getGroupId(), Constants::i18nMsg('breedingchains-evo'));

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

	private function calcMiddleColumnX (VisualSubtree $visualSubtree):int {
        $firstRoot = $visualSubtree->getRoots()[0];
        if (is_null($firstRoot)) {
            Logger::elog($visualSubtree.' has no roots');
            return 0;
        }
		return $firstRoot->getMiddleX() + Constants::PKMN_MARGIN_HORIZONTAL / 1.5;
	}

	private function addLeftHalfConnectionLines (VisualSubtree $subtree) {
		$this->addPkmnMiddleConnections($subtree);
		$this->addMiddleLine($subtree);
	}

	private function addPkmnMiddleConnections (VisualSubtree $subtree) {
		Logger::statusLog('adding line from '.$this.' middle line');
        foreach ($subtree->getRoots() as $root) {
		    $horizontalLine = $this->createPkmnMiddleConnection($root);
		    $this->addConnections([$horizontalLine]);
        }
	}

	private function createPkmnMiddleConnection (VisualNode $node): SVGPkmnConnection {
		$startX = $this->calculateNodeConnectionMarginRight($node);
		$startY = $node->getMiddleY();
		$horizontalLine = SVGPkmnConnection::constructWithoutText(
			$startX, $startY,
			$this->middleColumnX, $startY, $node->getGroupId());
		
		return $horizontalLine;
	}

	private function calculateNodeConnectionMarginRight (VisualNode $node): int {
		return $node->getMiddleX() + $node->calculateDiagonal()/2 
			+ Constants::SVG_CIRCLE_MARGIN;
	}

	private function addMiddleLine (VisualSubtree $visualSubtree) {
		Logger::statusLog('adding middle line after '.$this);
		$middleLine = $this->createMiddleLine($visualSubtree);
		$this->addConnections([$middleLine]);
	}

	private function createMiddleLine (VisualSubtree $visualSubtree): SVGPkmnConnection {
		$lowestY = $this->getLowestYCoordinateFromTreeSection($visualSubtree);
		$highestY = $this->getHighestYCoordinateFromTreeSection($visualSubtree);	

		$verticalLine = SVGPkmnConnection::constructWithoutText(
			$this->middleColumnX, $lowestY,
			$this->middleColumnX, $highestY, Constants::UNUSED_GROUP_ID);
		
		return $verticalLine;
	}

	private function getLowestYCoordinateFromTreeSection (VisualSubtree $subtree): int {
		$firstSuccessor = $subtree->getSuccessors()[0];
        $firstRoot = $subtree->getRoots()[0];

        if (is_null($firstRoot)) {
            Logger::elog($this.' has no roots');
            return 0;
        }

        if (is_null($firstSuccessor)) {
            return $firstRoot->getMiddleY();
        }

        $firstSuccessorRoot = $firstSuccessor->getRoots()[0];
        if (is_null($firstSuccessorRoot)) {
            Logger::elog('first successor of '.$this.' has no roots');
            return 0;
        }

        return min($firstSuccessorRoot->getMiddleY(), $firstRoot->getMiddleY());
	}

	private function getHighestYCoordinateFromTreeSection (VisualSubtree $subtree): int {
        $successors = $subtree->getSuccessors();
		$lastSuccessor = $successors[count($successors) - 1];

        $roots = $subtree->getRoots();
        $lastRoot = $roots[count($roots) - 1];

        if (is_null($lastRoot)) {
            Logger::elog($this.' has no roots');
            return 0;
        }

        if (is_null($lastSuccessor)) {
            return $lastRoot->getMiddleY();
        }

        $lastSuccessorRoots = $lastSuccessor->getRoots();
        $lastSuccessorRoot = $lastSuccessorRoots[count($lastSuccessorRoots) - 1];
        if (is_null($lastSuccessorRoot)) {
            Logger::elog('last successor of '.$this.' has no roots');
            return 0;
        }

        return max($lastSuccessorRoot->getMiddleY(), $lastRoot->getMiddleY());
	}	

	private function addMiddleToSuccessorConnections (VisualSubtree $visualSubtree) {
        foreach ($visualSubtree->getSuccessors() as $successorSubtree) {
            foreach ($successorSubtree->getRoots() as $successorNode) {
                $this->addMiddleToSuccessorConnection($successorNode);
            }
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
        foreach ($this->nodeLinks as $nodeLink) {
    		array_push(
    			$htmlTagCreationOptions['tagArray'],
    			$nodeLink->toHTML($htmlTagCreationOptions['xOffset'], $htmlTagCreationOptions['yOffset'])
    		);
        }
	}

	private function addCircle (array &$htmlTagCreationOptions) {
		$circleColor = '#3388ff';

        foreach ($this->visualRoots as $root) {        
		    if ($root->getDisplayOldGenMarker()) {
		    	$circleColor = 'yellow';
		    } else if ($root->getDisplayEventMarker()) {
		    	$circleColor = 'green';
		    }

    		$circle = $this->createCircle(
                $root,
    			$htmlTagCreationOptions['xOffset'],
    			$htmlTagCreationOptions['yOffset'],
    			$circleColor
    		);

    		array_push(
    			$htmlTagCreationOptions['tagArray'],
    			$circle
    		);
        }
	}

	private function createCircle (VisualNode $node, int $xOffset, int $yOffset, string $color): HTMLElement {
		$middleX = $node->getMiddleX();
		$middleY = $node->getMiddleY();
		$radius = $this->calculateOldGenMarkerRadius($node);

		$oldGenMarker = new SVGCircle(
			$middleX, $middleY, $radius, $color, $node);

		return $oldGenMarker->toHTML($xOffset, $yOffset);
	}

	private function calculateOldGenMarkerRadius (VisualNode $node): int {
		$diagonal = $node->calculateDiagonal();
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

	private function addSuccessorTagsToHTMLTagArray (array &$htmlTagCreationOptions, SVGSubtree $successor) {
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
		return 'SVGSubtree;;';
	}

	public function __toString (): string {
		return $this->getLogInfo();
	}
}