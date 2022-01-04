<?php
require_once __DIR__.'/../Pkmn.php';
require_once __DIR__.'/../exceptions/FileNotFoundException.php';
require_once __DIR__.'/../Logger.php';

use MediaWiki\MediaWikiServices;

class FrontendPkmn extends Pkmn {
    private bool $learnsByEvent;
    private bool $learnsByOldGen;
    private Array $successors = [];

    private int $x;
    private int $y;
    private int $treeSectionHeight;

    private string $iconUrl;
    private int $iconWidth;
    private int $iconHeight;
    private ?FileNotFoundException $fileError = null;

    public function __construct (BreedingTreeNode $breedingTreeNode) {
        parent::__construct($breedingTreeNode->getName(), $breedingTreeNode->getID());

        $this->learnsByEvent = $breedingTreeNode->getLearnsByEvent();
        $this->learnsByOldGen = $breedingTreeNode->getLearnsByOldGen();

        foreach ($breedingTreeNode->getSuccessors() as $successorTreeNode) {
            $successorFrontendObj = new FrontendPkmn($successorTreeNode);
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

    public function setTreeIconsAndCoordinates () {
        $this->setIconData();

        $this->calcTreeSectionHeights();
        $this->calcYCoords(0);

        $this->calcXCoords();
    }

    private function calcYCoords (int $sectionOffset): int {
        $yCoord = $sectionOffset;
        if ($this->hasSuccessors()) {
            $yCoord += $this->treeSectionHeight / 2;
        }
        Logger::statusLog('calculated y '.$yCoord.' of '.$this);
        $this->y = $yCoord;

        $successorOffset = $sectionOffset;
        foreach ($this->successors as $successor) {
            $successorSectionHeight = $successor->calcYCoords($successorOffset);
            $successorOffset += $successorSectionHeight;
        }

        Logger::statusLog('returning tree section height '
            .$this->treeSectionHeight.' of '.$this);
        return $this->treeSectionHeight;
    }

    private function calcXCoords (int $deepness = 0) {
        $this->x = $deepness * Constants::PKMN_MARGIN_HORI - $this->getIconWidth() / 2;
        Logger::statusLog('calculated x coordinate of '.$this);
        foreach ($this->successors as $successor) {
            $successor->calcXCoords($deepness + 1);
        }
    }

    private function setIconData () {
        try {
            $iconFileObj = $this->getPkmnIcon($this->id);
            Logger::statusLog('icon file for '.$this.' successfully loaded');

            $this->iconUrl = $iconFileObj->getUrl();
            $this->iconWidth = $iconFileObj->getWidth();
            $this->iconHeight = $iconFileObj->getHeight();
        } catch (FileNotFoundException $e) {
            Logger::statusLog('couldnt load file obj of '.$this.', setting file error');
            $this->fileError = $e;
        }

        foreach ($this->successors as $successor) {
            $successor->setIconData();
        }
    }

    private function calcTreeSectionHeights (): int {
        if (!$this->hasSuccessors()) {
            $height = $this->getHeight() + Constants::SVG_PKMN_SAFETY_MARGIN;
            Logger::statusLog(
                $this.' has no successors => setting and returning minimal height '.$height);
            $this->treeSectionHeight = $height;
            return $height;
        }

        $heightSum = 0;
        foreach ($this->successors as $successor) {
            $successorTreeSectionHeight = $successor->calcTreeSectionHeights();
            $heightSum += $successorTreeSectionHeight;
        }
        Logger::statusLog('calculated tree section height '.$heightSum.' of '.$this);
        $this->treeSectionHeight = $heightSum;
        return $heightSum;
    }

    private function getPkmnIcon (string $pkmnId): File {
        $fileName = 'Pokémon-Icon '.$pkmnId.'.png';
        $fileObj = MediaWikiServices::getInstance()->getRepoGroup()->findFile($fileName);

        if ($fileObj === false) {
            throw new FileNotFoundException($pkmnId);
        }

        return $fileObj;
    }

    public function addSuccessor (FrontendPkmn $successor) {
        array_push($this->successors, $successor);
    }

    public function hasSuccessors (): bool {
        return count($this->successors) > 0;
    }

    public function getSuccessors (): Array {
        return $this->successors;
    }

    public function getLearnsByEvent (): bool {
        return $this->learnsByEvent;
    }

    public function getLearnsByOldGen (): bool {
        return $this->learnsByOldGen;
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

    //==================================================
    //getters with actual logic (:OOOOOOOOO)
    //correction: they had logic

    public function getX (): int {
        $retX = $this->x;
        return $retX;
    }

    public function getMiddleX (): int {
        $middleX = $this->getX() + $this->getWidth() / 2;
        Logger::statusLog('returning '.$middleX.' in getMiddleX call of '.$this);
        return $middleX;
    }

    public function getIconX (): int {
        return $this->getX();
    }

    public function getY (): int {
        $retY = $this->y;
        return $retY;
    }

    public function getMiddleY (): int {
        $middleY = $this->getY() + $this->getHeight() / 2;
        Logger::statusLog('returning middle y '
            .$middleY.' in getMiddleY call on '.$this);
        return $middleY;
    }

    public function getWidth (): int {
        return $this->iconWidth;
    }

    public function getHeight (): int {
        return $this->iconHeight;
    }

    public function getLogInfo (): string {
        $msg = 'FrontendPkmn:\'\'\''.$this->name.'\'\'\';('
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