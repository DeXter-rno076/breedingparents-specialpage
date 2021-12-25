<?php
require_once __DIR__.'/../Logger.php';
require_once 'SVGImg.php';
require_once 'SVGIconErrorText.php';
require_once 'SVGLine.php';

class SVGPkmn {
    private ?SVGImg $icon = null;
    private ?SVGIconErrorText $iconEText = null;
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
        //todo add link
        $pkmn = $this->nodePkmn;
        Logger::statusLog('adding pkmn icon of '.$pkmn);
        $fileE = $pkmn->getFileError();
        if (is_null($fileE)) {
            Logger::statusLog($pkmn.' has no file error set => can add icon');
            $pkmnIcon = new SVGImg($pkmn);
            $this->icon = $pkmnIcon;
            return;
        }
        Logger::statusLog($pkmn.' has file error '.$fileE.' set => adding error tetx');
        $eText = new SVGIconErrorText($pkmn);
        $this->iconEText = $eText;
    }

    private function addConnectionStructure () {
        $pkmn = $this->nodePkmn;
        Logger::statusLog('adding connection structure after '.$pkmn);

        if (!$pkmn->hasSuccessors()) {
            Logger::statusLog($pkmn.' has no successors => not adding any lines');
            return;
        }

        //todo needs special handling for exactly one successor

        $this->middleColumnX = $pkmn->getMiddleX() + Constants::PKMN_MARGIN_HORI / 1.5;
        Logger::statusLog('calculated middle column at '.$this->middleColumnX);
        $this->addLeftHalfConnectionLines();
        $this->addMiddleSuccessorConnections();
    }

    private function addLeftHalfConnectionLines () {
        $pkmn = $this->nodePkmn;
        Logger::statusLog('adding left half of the connection structure of '.$pkmn);
        $this->addPkmnMiddleConnection();
        $this->addMiddleLine();
    }

    private function addPkmnMiddleConnection () {
        Logger::statusLog('adding line from pkmn icon to middle line of '.$this->nodePkmn);
        $pkmn = $this->nodePkmn;
        $horiStartX = $pkmn->getX() + $pkmn->getIconWidth() 
            + Constants::PKMN_ICON_LINE_MARGIN;
        $horiY = $pkmn->getMiddleY();
        $horizontalLine = new SVGLine(
            $horiStartX, $horiY,
            $this->middleColumnX, $horiY);
        Logger::statusLog('calculated line '.$horizontalLine);
        array_push($this->lineConnections, $horizontalLine);
    }

    private function addMiddleLine () {
        $pkmn = $this->nodePkmn;
        Logger::statusLog('adding middle line of '.$pkmn);
        $successors = $pkmn->getSuccessors();
        $firstSuccessor = $successors[0];
        $lastSuccessor = $successors[count($successors) - 1];
        Logger::statusLog('first successor: '.$firstSuccessor
            .'last successor: '.$lastSuccessor);

        $lowestY = $firstSuccessor->getMiddleY();
        $highestY = $lastSuccessor->getMiddleY();

        $vertialLine = new SVGLine(
            $this->middleColumnX, $lowestY,
            $this->middleColumnX, $highestY);
        Logger::statusLog('calculated line '.$vertialLine);
        array_push($this->lineConnections, $vertialLine);
    }

    private function addMiddleSuccessorConnections () {
        Logger::statusLog('adding lines from middle to successors of '.$this->nodePkmn);
        foreach ($this->nodePkmn->getSuccessors() as $successor) {
            Logger::statusLog('adding line to from vertical middle line to '
                .'successor icon of '.$this);
            $startX = $this->middleColumnX;
            $endX = $successor->getX() - Constants::PKMN_ICON_LINE_MARGIN;
            $y = $successor->getMiddleY();

            $line = new SVGLine($startX, $y, $endX, $y);
            array_push($this->lineConnections, $line);
        }
    }

    public function toHTMLString (): string {
        $outputString = '';
        if (!is_null($this->icon)) {
            $outputString .= $this->icon->toHTMLString();
        }
        if (!is_null($this->iconEText)) {
            $outputString .= $this->iconEText->toHTMLString();
        }

        foreach ($this->lineConnections as $line) {
            $outputString .= $line->toHTMLString();
        }

        foreach ($this->successors as $successor) {
            $outputString .= $successor->toHTMLString();
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