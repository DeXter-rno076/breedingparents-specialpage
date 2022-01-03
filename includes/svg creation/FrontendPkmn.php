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

    private const EVENT_TEXT_HEIGHT = 20;
    private const EVENT_TEXT_WIDTH = 41;
    private const SAFETY_SPACE = 10;

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
        $yCoord = $sectionOffset + $this->treeSectionHeight / 2;
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
        /* todo sometimes this is returns too small heights (Glumanda, Biss, Gen8)
        but this doesn't stand out because of the safety margin*/
        if (!$this->hasSuccessors()) {
            $height = $this->getHeight() + self::SAFETY_SPACE;
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
        return $heightSum + self::SAFETY_SPACE;
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

    public function getX (): int {
        $retX = $this->x + self::SAFETY_SPACE;
        Logger::statusLog('returning '.$retX.' in getX call of '.$this);
        return $retX;
    }

    public function getMiddleX (): int {
        $middleX = $this->getX() + $this->getWidth() / 2;
        Logger::statusLog('returning '.$middleX.' in getMiddleX call of '.$this);
        return $middleX;
    }

    public function getEventTextX (): int {
        Logger::statusLog('calling '.__FUNCTION__.' of '.$this);
        $eventTextX = $this->getX() + $this->getPartXOffset(
            self::EVENT_TEXT_WIDTH,
            $this->iconWidth
        );
        Logger::statusLog('returning '.$eventTextX.' in getEventTextX call of '.$this);
        return $eventTextX; 
    }

    public function getIconX (): int {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        if (!$this->learnsByEvent) {
            $normalX = $this->getX();
            Logger::statusLog('returning normal x '
                .$normalX.' in getIconX call on '.$this);
            return $normalX;
        }
        $indentedX = $this->getX() + $this->getPartXOffset(
            $this->iconWidth,
            self::EVENT_TEXT_WIDTH
        );
        Logger::statusLog('returning indented x '
            .$indentedX.' in getIconX call of '.$this);
        return $indentedX;
    }

    //todo this name is shit
    private function getPartXOffset (
            int $targetWidth, int $otherWidth): int {
        /*depending on which is wider, the icon or
        the text have to be indented a bit*/
        if ($otherWidth > $targetWidth) {
            $indentation = ($otherWidth - $targetWidth) / 2;
            Logger::statusLog('returning indentation '
                .$indentation.' in getPartXOffset call on '.$this);
            return $indentation;
        }
        Logger::statusLog('width of target is greater or equal => returning 0 indentation');
        return 0;
    }

    public function getY (): int {
        $retY = $this->y + self::SAFETY_SPACE;
        Logger::statusLog('returning y '.$retY.' in getY call on '.$this);
        return $retY;
    }

    public function getMiddleY (): int {
        $middleY = $this->getY() + $this->getHeight() / 2;
        Logger::statusLog('returning middle y '
            .$middleY.' in getMiddleY call on '.$this);
        return $middleY;
    }

    public function getWidth (): int {
        if (!$this->learnsByEvent) {
            Logger::statusLog($this.' doesn\'t learn via event '
                .'=> returning icon width '.$this->iconWidth);
            return $this->iconWidth;
        }
        $retWidth = max($this->iconWidth, self::EVENT_TEXT_WIDTH);
        Logger::statusLog('returning width '.$retWidth);
        return $retWidth;
    }

    public function getHeight (): int {
        if (!$this->learnsByEvent) {
            Logger::statusLog($this.' doesn\'t learn via event '
                .' returning icon width '.$this->iconWidth);
            return $this->iconHeight;
        }
        $retHeight = $this->iconHeight
        + self::EVENT_TEXT_HEIGHT / 2;
        Logger::statusLog('returning height '.$retHeight);
        return $retHeight;
    }

    public function getLogInfo (): string {
        $msg = 'FrontendPkmn:'.$this->name.';('
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