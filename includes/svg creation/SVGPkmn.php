<?php
require_once __DIR__.'/../Logger.php';
require_once 'SVGImg.php';
require_once 'SVGLine.php';
require_once 'SVGLink.php';
require_once 'SVGCircle.php';
require_once 'SVGRectangle.php';
require_once 'SVGText.php';
require_once __DIR__.'/../HTMLElement.php';

class SVGPkmn {
    private ?SVGLink $pkmnLink = null;
    private Array $lineConnections = [];
    private Array $successors = [];
    private FrontendPkmn $nodeFrontendPkmn;

    private int $middleColumnX = 0;

    public function __construct (FrontendPkmn $pkmn) {
        $this->nodePkmn = $pkmn;
        $this->addIcon();
        $this->addConnectionStructure();
        foreach ($this->nodePkmn->getSuccessors() as $successor) {
            $svgSuccessor = new SVGPkmn($successor);
            array_push($this->successors, $svgSuccessor);
        }
    }

    /**
     * Creates and adds SVGImg and SVGLink instances for this node
     * if the icon for this node could be loaded.
     */
    private function addIcon () {
        $pkmn = $this->nodePkmn;
        $fileE = $pkmn->getFileError();
        if (is_null($fileE)) {
            Logger::statusLog($pkmn.' has no file error set => can add icon');
            $pkmnIcon = new SVGImg($pkmn);
            $link = new SVGLink($pkmn->getName(), $pkmnIcon);
            $this->link = $link;
            return;
        }
    }

    private function addConnectionStructure () {
        $pkmn = $this->nodePkmn;

        if (!$pkmn->hasSuccessors()) {
            Logger::statusLog($pkmn.' has no successors => not adding any lines');
            return;
        } else if (count($pkmn->getSuccessors()) === 1) {
            if ($pkmn->getIsRoot() && $pkmn->getSuccessors()[0]->getIsRoot()) {
                $this->addEvoConnection();
            }
        }

        $this->middleColumnX = $pkmn->getMiddleX() + Constants::PKMN_MARGIN_HORIZONTAL / 1.5;
        $this->addLeftHalfConnectionLines();
        $this->addMiddleSuccessorConnections();
    }

    private function addLeftHalfConnectionLines () {
        $this->addPkmnMiddleConnection();
        $this->addMiddleLine();
    }

    private function addPkmnMiddleConnection () {
        $pkmn = $this->nodePkmn;
        $horiStartX = $pkmn->getX() + $pkmn->getIconWidth()
            + Constants::PKMN_ICON_LINE_MARGIN;
        $horiY = $pkmn->getMiddleY();
        $horizontalLine = new SVGLine(
            $horiStartX, $horiY,
            $this->middleColumnX, $horiY);
        array_push($this->lineConnections, $horizontalLine);
    }

    private function addMiddleLine () {
        $pkmn = $this->nodePkmn;
        $successors = $pkmn->getSuccessors();
        $firstSuccessor = $successors[0];
        $lastSuccessor = $successors[count($successors) - 1];

        $lowestY = $firstSuccessor->getMiddleY();
        $highestY = $lastSuccessor->getMiddleY();

        if (count($successors) === 1) {
            $this->adjustCoordinatesToOnlyOneSuccessor($lowestY, $highestY);
        }

        $vertialLine = new SVGLine(
            $this->middleColumnX, $lowestY,
            $this->middleColumnX, $highestY);
        array_push($this->lineConnections, $vertialLine);
    }


    /**
     * If this node only has one successor start and end coordinates of the
     * vertical middle line have to be adjusted to connect the lines of 
     * this node and its successor.
     * @param int &$lowestY
     * @param int &$highestY
     */
    private function adjustCoordinatesToOnlyOneSuccessor (int &$lowestY, int &$highestY) {
        $successor = $this->nodePkmn->getSuccessors()[0];

        $nodeMiddleY = $this->nodePkmn->getMiddleY();
        $successorMiddleY = $successor->getMiddleY();

        $lowestY = min($nodeMiddleY, $successorMiddleY);
        $highestY = max($nodeMiddleY, $successorMiddleY);
    }

    /**
     * Creates and adds the lines from the vertical middle line to the successors
     * of this node.
     */
    private function addMiddleSuccessorConnections () {
        foreach ($this->nodePkmn->getSuccessors() as $successor) {
            $startX = $this->middleColumnX;
            $endX = $successor->getX() - Constants::PKMN_ICON_LINE_MARGIN;
            $y = $successor->getMiddleY();

            $line = new SVGLine($startX, $y, $endX, $y);
            array_push($this->lineConnections, $line);
        }
    }

    /**
     * If the root is an evolution, the connection to its lowest evo is an
     * arrow and gets a text to additionally show that this is an evo connection. 
     */
    private function addEvoConnection () {
        Logger::statusLog('adding evo arrow line connection for '.$this);
        $pkmn = $this->nodePkmn;
        $evo = $pkmn->getSuccessors()[0];

        $startX = $pkmn->getX() + $pkmn->getIconWidth()
            + Constants::PKMN_ICON_LINE_MARGIN;
        $endX = $evo->getX() - Constants::PKMN_ICON_LINE_MARGIN;
        $y = $pkmn->getMiddleY();
        $horizontalLine = new SVGLine($startX, $y, $endX, $y);
        $upperArrowPart = new SVGLine($startX, $y, $startX + 10, $y - 10);
        $lowerArrowPart = new SVGLine($startX, $y, $startX + 10, $y + 10);

        $connectionText = new SVGText(
			$startX + 30, $y - 2, Constants::$centralSpecialPageInstance->msg('breedingparents-evo'));

        array_push($this->lineConnections, $horizontalLine);
        array_push($this->lineConnections, $upperArrowPart);
        array_push($this->lineConnections, $lowerArrowPart);

        //wer nicht pfuschen kann, kann nicht arbeiten
        array_push($this->lineConnections, $connectionText);
    }

    public function toHTML (int $xOffset, int $yOffset): array {
        $tagArray = [];
        if (!is_null($this->link)) {
            array_push($tagArray, $this->link->toHTML($xOffset, $yOffset));
        } else {
            Constants::error($this->nodePkmn->getFileError());
        }

        if ($this->nodePkmn->getLearnsByOldGen()) {
            array_push($tagArray, $this->createOldGenMarker($xOffset, $yOffset));
        } else if ($this->nodePkmn->getLearnsByEvent()) {
            array_push($tagArray, $this->createEventMarker($xOffset, $yOffset));
        }

        foreach ($this->lineConnections as $line) {
            array_push($tagArray, $line->toHTML($xOffset, $yOffset));
        }

        foreach ($this->successors as $successor) {
            foreach ($successor->toHTML($xOffset, $yOffset) as $tag) {
                array_push($tagArray, $tag);
            }
        }

        return $tagArray;
    }

    private function createOldGenMarker (int $xOffset, int $yOffset): HTMLElement {
        $middleX = $this->nodePkmn->getMiddleX();
        $middleY = $this->nodePkmn->getMiddleY();
        $width = $this->nodePkmn->getWidth();

        $oldGenMarker = new SVGCircle($middleX, $middleY, $width / 2 + Constants::SVG_CIRCLE_MARGIN);

        return $oldGenMarker->toHTML($xOffset, $yOffset);
    }

    private function createEventMarker (int $xOffset, int $yOffset): HTMLElement {
        $x = $this->nodePkmn->getX();
        $y = $this->nodePkmn->getY();
        $width = $this->nodePkmn->getWidth();
        $height = $this->nodePkmn->getHeight();

        $eventMarker = new SVGRectangle(
            $x - Constants::SVG_RECTANGLE_PADDING,
            $y - Constants::SVG_RECTANGLE_PADDING,
            $width + 2 * Constants::SVG_RECTANGLE_PADDING,
            $height + 2 * Constants::SVG_RECTANGLE_PADDING
        );

        return $eventMarker->toHTML($xOffset, $yOffset);
    }

    /**
     * @return string SVGPkmn:<frontendPkmn instance>
     */
    public function getLogInfo (): string {
        return 'SVGPkmn:'.$this->nodePkmn;
    }

    public function __toString (): string {
        return $this->getLogInfo();
    }
}