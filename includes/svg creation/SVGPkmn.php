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

    public function toHTMLString (int $xOffset, int $yOffset): string {
        $outputString = '';
        if (!is_null($this->link)) {
            $outputString .= $this->link->toHTMLString($xOffset, $yOffset);
        } else {
            Constants::error($this->nodePkmn->getFileError());
        }

        foreach ($this->lineConnections as $line) {
            $outputString .= $line->toHTMLString($xOffset, $yOffset);
        }

        foreach ($this->successors as $successor) {
            $outputString .= $successor->toHTMLString($xOffset, $yOffset);
        }

        return $outputString;
    }

    public function getLogInfo (): string {
        return 'SVGPkmn:'.$this->nodePkmn;
    }

    public function __toString (): string {
        return $this->getLogInfo();
    }
}