<?php
require_once __DIR__.'/../output_messages/ErrorMessage.php';
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';
require_once __DIR__.'/../exceptions/FileNotFoundException.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../Constants.php';

require_once 'VisualComplex.php';
require_once 'VisualImg.php';
require_once 'VisualLine.php';
require_once 'VisualLink.php';
require_once 'VisualCircle.php';
require_once 'VisualText.php';
require_once 'VisualPreparationNode.php';
require_once 'VisualConnection.php';
require_once 'VisualPreparationSubtree.php';

abstract class VisualSubtree extends VisualComplex {
    private $nodeLinks = [];
    private $nodeConnections = [];
    private $successors = [];
    private $visualRoots;

    private $middleColumnX = 0;

    public function __construct (VisualPreparationSubtree $visualPrepSubtree) {
        $this->visualRoots = $visualPrepSubtree->getRoots();
        $this->addIcons($visualPrepSubtree->getRoots());
        $this->addConnectionStructure($visualPrepSubtree);

        foreach ($visualPrepSubtree->getSuccessors() as $successor) {
            try {
                $successorSubtree = $this->instantiateVisualSubtree($successor);
                array_push($this->successors, $successorSubtree);
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

    private function addIcon (VisualPreparationNode $visualRoot) {
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

    private function addConnectionStructure (VisualPreparationSubtree $visualPrepSubtree) {
        Logger::statusLog('adding connection structure after '.$this);

        if (!$visualPrepSubtree->hasSuccessors()) {
            Logger::statusLog($visualPrepSubtree.' has no successors => not adding any lines');
        } else if ($visualPrepSubtree->hasEvoConnection()) {
            if (count($visualPrepSubtree->getRoots()) > 1 || count($visualPrepSubtree->getSuccessors()) > 1) {
                Logger::elog($visualPrepSubtree.' has multiple roots or successors even though it has an evo connection');
                return;
            }
            $this->addEvoConnection($visualPrepSubtree);
        } else {
            Logger::statusLog($this.' has no evo connection -> adding standard line structure');
            $this->middleColumnX = $this->calcMiddleColumnX($visualPrepSubtree);
            $this->addLeftHalfConnectionLines($visualPrepSubtree);
            $this->addMiddleToSuccessorConnections($visualPrepSubtree);
        }
    }

    /**
     * if the root is an evolution, the connection to its lowest evo is an
     * arrow and gets a text to additionally show that this is an evo connection.
     */
    private function addEvoconnection (VisualPreparationSubtree $visualPrepSubtree) {
        Logger::statusLog('adding evo arrow line connection for '.$this);

        $connectionParts = $this->createEvoconnectionParts($visualPrepSubtree);
        $this->addConnections($connectionParts);
    }

    private function createEvoConnectionParts (VisualPreparationSubtree $visualPrepSubtree): array {
        $visualRoot = $visualPrepSubtree->getRoots()[0];
        $groupId = $visualRoot->getGroupId();
        if (is_null($visualRoot)) {
            Logger::elog('unexpected empty roots array of '.$visualRoot);
            return [];
        }
        $evoFrontendPkmnInstance = $visualPrepSubtree->getFirstPkmnSuccessor();

        $startX = $this->calculateNodeConnectionMarginRight($visualRoot);
        $endX = $this->calculateNodeConnectionMarginLeft($evoFrontendPkmnInstance);
        $y = $visualRoot->getMiddleY();

        $horizontalLine = $this->instantiateVisualLine($startX, $y, $endX, $y, $groupId);
        $horizontalConnection = $this->instantiateVisualConnection($horizontalLine, $groupId, Constants::i18nMsg('breedingchains-evo'));
        
        $upperArrowLine = $this->instantiateVisualLine($startX, $y, $startX + 10, $y -10, $groupId);
        $upperArrowConnection = $this->instantiateVisualConnection($upperArrowLine, $groupId);

        $lowerArrowLine = $this->instantiateVisualLine($startX, $y, $startX + 10, $y + 10, $groupId);
        $lowerArrowConnection = $this->instantiateVisualConnection($lowerArrowLine, $groupId);
        
        return [
            $horizontalConnection, $upperArrowConnection, $lowerArrowConnection
        ];
    }

    private function calculateNodeConnectionMarginLeft (VisualPreparationNode $successor): int {
        return $successor->getMiddleX() - $successor->calculateDiagonal()/2
            - Constants::VISUAL_CIRCLE_MARGIN;
    }

    private function addConnections (array $connections) {
        foreach ($connections as $connection) {
            array_push($this->nodeConnections, $connection);
        }
    }

    private function calcMiddleColumnX (VisualPreparationSubtree $visualPrepSubtree):int {
        $firstRoot = $visualPrepSubtree->getRoots()[0];
        if (is_null($firstRoot)) {
            Logger::elog($visualPrepSubtree.' has no roots');
            return 0;
        }
        return $firstRoot->getMiddleX() + Constants::VISUAL_NODE_MARGIN_HORIZONTAL / 1.5;
    }

    private function addLeftHalfConnectionLines (VisualPreparationSubtree $subtree) {
        $this->addPkmnMiddleConnections($subtree);
        $this->addMiddleLine($subtree);
    }

    private function addPkmnMiddleConnections (VisualPreparationSubtree $subtree) {
        Logger::statusLog('adding line from '.$this.' middle line');
        foreach ($subtree->getRoots() as $root) {
            $horizontalLine = $this->createPkmnMiddleConnection($root);
            $this->addConnections([$horizontalLine]);
        }
    }

    private function createPkmnMiddleConnection (VisualPreparationNode $node): VisualConnection {
        $startX = $this->calculateNodeConnectionMarginRight($node);
        $startY = $node->getMiddleY();
        $horizontalLine = $this->instantiateVisualLine($startX, $startY,
            $this->middleColumnX, $startY, $node->getGroupId());
        $horizontalConnection = $this->instantiateVisualConnection($horizontalLine, $node->getGroupId());

        return $horizontalConnection;
    }

    private function calculateNodeConnectionMarginRight (VisualPreparationNode $node): int {
        return $node->getMiddleX() + $node->calculateDiagonal()/2
            + Constants::VISUAL_CIRCLE_MARGIN;
    }

    private function addMiddleLine (VisualPreparationSubtree $visualPrepSubtree) {
        Logger::statusLog('adding middle line after '.$this);
        $middleLine = $this->createMiddleLine($visualPrepSubtree);
        $this->addConnections([$middleLine]);
    }

    private function createMiddleLine (VisualPreparationSubtree $visualPrepSubtree): VisualConnection {
        $lowestY = $this->getLowestYCoordinateFromTreeSection($visualPrepSubtree);
        $highestY = $this->getHighestYCoordinateFromTreeSection($visualPrepSubtree);

        $verticalLine = $this->instantiateVisualLine($this->middleColumnX, $lowestY,
            $this->middleColumnX, $highestY, Constants::UNUSED_GROUP_ID);
        $verticalConnection = $this->instantiateVisualConnection($verticalLine, Constants::UNUSED_GROUP_ID);

        return $verticalConnection;
    }

    private function getLowestYCoordinateFromTreeSection (VisualPreparationSubtree $subtree): int {
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

    private function getHighestYCoordinateFromTreeSection (VisualPreparationSubtree $subtree): int {
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

    private function addMiddleToSuccessorConnections (VisualPreparationSubtree $visualPrepSubtree) {
        foreach ($visualPrepSubtree->getSuccessors() as $successorSubtree) {
            foreach ($successorSubtree->getRoots() as $successorNode) {
                $this->addMiddleToSuccessorConnection($successorNode);
            }
        }
    }

    private function addMiddleToSuccessorConnection (VisualPreparationNode $successor) {
        Logger::statusLog('adding connection from middle to '.$successor);
        $middleToSuccessorConnection = $this->createMiddleToSuccessorConnection($successor);
        $this->addConnections([$middleToSuccessorConnection]);
    }

    private function createMiddleToSuccessorConnection (VisualPreparationNode $successor): VisualConnection {
        $startX = $this->middleColumnX;// - Constants::VISUAL_LINE_WIDTH/2;
        $endX = $this->calculateNodeConnectionMarginLeft($successor);
        $y = $successor->getMiddleY();

        $line = $this->instantiateVisualLine($startX, $y, $endX, $y, $successor->getGroupId());
        $connection = $this->instantiateVisualConnection($line, $successor->getGroupId());
        
        return $connection;
    }

    public function compile (int $xOffset, int $yOffset): array {
        $tagArray = [];

        $creationOptions = [
            'tagArray' => &$tagArray,
            'xOffset' => $xOffset,
            'yOffset' => $yOffset
        ];

        $this->addCircles($creationOptions);
        $this->addIconsOrFileErrorsToHTMLTagArray($creationOptions);
        $this->addnodeConnectionsToHTMLTagArray($creationOptions);
        $this->addSuccessorsToHTMLTagArray($creationOptions);

        return $tagArray;
    }

    private function addIconsOrFileErrorsToHTMLTagArray (array &$creationOptions) {
        foreach ($this->nodeLinks as $nodeLink) {
            array_push(
                $creationOptions['tagArray'],
                $nodeLink->compile($creationOptions['xOffset'], $creationOptions['yOffset'])
            );
        }
    }

    private function addCircles (array &$creationOptions) {
        foreach ($this->visualRoots as $root) {
            $circleColor = '#3388ff';
            if ($root->getDisplayOldGenMarker()) {
                $circleColor = '#ee0';
            } else if ($root->getDisplayEventMarker()) {
                $circleColor = 'green';
            }

            $circle = $this->createCircle($root, $circleColor)->compile(
                $creationOptions['xOffset'], $creationOptions['yOffset']);

            array_push(
                $creationOptions['tagArray'],
                $circle
            );
        }
    }

    private function createCircle (VisualPreparationNode $node, string $color): VisualCircle {
        $middleX = $node->getMiddleX();
        $middleY = $node->getMiddleY();
        $radius = $this->calculateOldGenMarkerRadius($node);

        return $this->instantiateVisualCircle($middleX, $middleY, $radius, $color, $node);
    }

    private function calculateOldGenMarkerRadius (VisualPreparationNode $node): int {
        $diagonal = $node->calculateDiagonal();
        $distanceFromMiddleToCorners = $diagonal / 2;

        return $distanceFromMiddleToCorners + Constants::VISUAL_CIRCLE_MARGIN;
    }

    private function addnodeConnectionsToHTMLTagArray (array &$creationOptions) {
        foreach ($this->nodeConnections as $connection) {
            $connectionParts = $connection->compile(
                $creationOptions['xOffset'],
                $creationOptions['yOffset']
            );
            foreach ($connectionParts as $part) {
                array_push(
                    $creationOptions['tagArray'],
                    $part
                );
            }
        }
    }

    private function addSuccessorsToHTMLTagArray (array &$creationOptions) {
        foreach ($this->successors as $successor) {
            $this->addSuccessorTagsToHTMLTagArray($creationOptions, $successor);
        }
    }

    private function addSuccessorTagsToHTMLTagArray (array &$htmlTagCreationOptions, VisualSubtree $successor) {
        $successorHTMLTags = $successor->compile(
            $htmlTagCreationOptions['xOffset'],
            $htmlTagCreationOptions['yOffset']
        );
        foreach ($successorHTMLTags as $tag) {
            array_push($htmlTagCreationOptions['tagArray'], $tag);
        }
    }

    protected abstract function instantiateVisualSubtree (VisualPreparationSubtree $subtree): VisualSubtree;
    protected abstract function createNodeIcon (VisualPreparationNode $node): VisualLink;
    protected abstract function instantiateVisualLine (int $x1, int $y1, int $x2, int $y2, int $groupId): VisualLine;
    protected abstract function instantiateVisualConnection (VisualLine $line,
        int $groupId, string $text = null): VisualConnection;
    protected abstract function instantiateVisualCircle (int $x, int $y,
        int $r, string $color, VisualPreparationNode $node): VisualCircle;

}