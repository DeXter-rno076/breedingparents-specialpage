<?php
require_once __DIR__.'/../Logger.php';
require_once 'SVGImg.php';
require_once 'SVGLine.php';
require_once 'SVGLink.php';

class SVGPkmn {
    private ?SVGLink $link = null;
    private Array $lineConnections = [];
    private Array $successors = [];

    private FrontendPkmn $nodePkmn;
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
        Logger::statusLog($pkmn.' has file error '.$fileE.' set => adding error tetx');
    }

    private function addConnectionStructure () {
        $pkmn = $this->nodePkmn;

        if (!$pkmn->hasSuccessors()) {
            Logger::statusLog($pkmn.' has no successors => not adding any lines');
            return;
        }

        $this->middleColumnX = $pkmn->getMiddleX() + Constants::PKMN_MARGIN_HORI / 1.5;
        $this->addLeftHalfConnectionLines();
        $this->addMiddleSuccessorConnections();
    }

    private function addLeftHalfConnectionLines () {
        $pkmn = $this->nodePkmn;
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

    private function adjustCoordinatesToOnlyOneSuccessor (int &$lowestY, int &$highestY) {
        $successor = $this->nodePkmn->getSuccessors()[0];

        $nodeMiddleY = $this->nodePkmn->getMiddleY();
        $successorMiddleY = $successor->getMiddleY();

        $lowestY = min($nodeMiddleY, $successorMiddleY);
        $highestY = max($nodeMiddleY, $successorMiddleY);
    }

    private function addMiddleSuccessorConnections () {
        foreach ($this->nodePkmn->getSuccessors() as $successor) {
            $startX = $this->middleColumnX;
            $endX = $successor->getX() - Constants::PKMN_ICON_LINE_MARGIN;
            $y = $successor->getMiddleY();

            $line = new SVGLine($startX, $y, $endX, $y);
            array_push($this->lineConnections, $line);
        }
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
            $x - Constants::SVG_RECT_PADDING,
            $y - Constants::SVG_RECT_PADDING,
            $width + 2 * Constants::SVG_RECT_PADDING,
            $height + 2 * Constants::SVG_RECT_PADDING
        );

        return $eventMarker->toHTML($xOffset, $yOffset);
    }

    public function getLogInfo (): string {
        return 'SVGPkmn:'.$this->nodePkmn;
    }

    public function __toString (): string {
        return $this->getLogInfo();
    }
}