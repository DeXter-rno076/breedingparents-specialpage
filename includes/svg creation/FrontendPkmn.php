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
        Logger::statusLog('instantiating FrontendPkmn instance of '.$breedingTreeNode);
        parent::__construct($breedingTreeNode->getName(), $breedingTreeNode->getID());
    
        $this->learnsByEvent = $breedingTreeNode->getLearnsByEvent();
        $this->learnsByOldGen = $breedingTreeNode->getLearnsByOldGen();

        foreach ($breedingTreeNode->getSuccessors() as $successorTreeNode) {
            $successorFrontendObj = new FrontendPkmn($successorTreeNode);
            $this->addSuccessor($successorFrontendObj);
        }        
    }

    public function getDepth (): int {
        Logger::statusLog('calculating highest depth of frontend tree');
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
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        $this->setIconData();

        $this->calcTreeSectionHeights();
        $this->calcYCoords(0);
        
        $this->calcXCoords();
    }

    private function calcYCoords (int $sectionOffset): int {
        Logger::statusLog('recursively calculating tree section y axis offsets of '
            .$this.' and its successors with sectionOffset '.$sectionOffset);
        $yCoord = $sectionOffset + $this->treeSectionHeight / 2;
        Logger::statusLog('calculated y '.$yCoord.' of '.$this);
        $this->y = $yCoord;

        $successorOffset = $sectionOffset;
        foreach ($this->successors as $successor) {
            $successorSectionHeight = $successor->calcYCoords($successorOffset);
            Logger::statusLog('got tree section height '
                .$successorSectionHeight.' from successor '.$successor);
            $successorOffset += $successorSectionHeight;
            Logger::statusLog('new successor offset of '.$this.' is '.$successorOffset);
        }

        Logger::statusLog('returning tree section height of '.$this);
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
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        try {
            $iconFileObj = $this->getPkmnIcon($this->id);
            Logger::statusLog('icon loading of '.$this.' successful');

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
        Logger::statusLog('recursively calculating tree section heights for '
            .$this.' and its successors');
        if (!$this->hasSuccessors()) {
            Logger::statusLog(
                $this.' has no successors => setting and returning minimal width');
            $height = $this->getHeight() + self::SAFETY_SPACE;
            $this->treeSectionHeight = $height;
            Logger::statusLog('minimal height of '.$this.' is '.$height);
            return $height;
        }
        Logger::statusLog(
            $this.' has successors => recursively calculating tree section heights');

        $heightSum = 0;
        foreach ($this->successors as $successor) {
            $successorTreeSectionHeight = $successor->calcTreeSectionHeights();
            $heightSum += $successorTreeSectionHeight;
        }
        Logger::statusLog('calculated tree section height '.$heightSum.' of '.$this);
        Logger::statusLog('setting and returning tree section height');
        $this->treeSectionHeight = $heightSum;
        return $heightSum + self::SAFETY_SPACE;
    }

    private function getPkmnIcon (string $pkmnId): File {
        Logger::statusLog('getting icon file of '.$pkmnId);
        $fileName = 'Pokémon-Icon '.$pkmnId.'.png';
		$fileObj = MediaWikiServices::getInstance()->getRepoGroup()->findFile($fileName);

		if ($fileObj === false) {
			throw new FileNotFoundException($pkmnId);
		}

		return $fileObj;
    }

    public function addSuccessor (FrontendPkmn $successor) {
        Logger::statusLog('calling '.__FUNCTION__.' on '
            .$this.', adding '.$successor);
        Logger::statusLog('successorlist length before: '.count($this->successors));
        array_push($this->successors, $successor);
        Logger::statusLog('successorlist length after: '.count($this->successors));
    }

    public function hasSuccessors (): bool {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        return count($this->successors) > 0;
    }

    public function getSuccessors (): Array {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        return $this->successors;
    }

    public function getLearnsByEvent (): bool {
        Logger::statusLog('calling '.__FUNCTION__.' of '
            .$this.' returning '.$this->learnsByEvent);
        return $this->learnsByEvent;
    }

    public function getLearnsByOldGen (): bool {
        Logger::statusLog('calling '.__FUNCTION__.' of '
            .$this.' returning '.$this->learnsByOldGen);
        return $this->learnsByOldGen;
    }

    public function getIconUrl (): string {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .' returning '.$this->iconUrl);
        return $this->iconUrl;
    }

    public function getIconWidth (): int {
        Logger::statusLog('calling '.__FUNCTION__.' of '
            .$this.' returning '.$this->iconWidth);
        return $this->iconWidth;
    }

    public function getIconHeight (): int {
        Logger::statusLog('calling '.__FUNCTION__.' of '
            .$this.' returning '.$this->iconHeight);
        return $this->iconHeight;
    }

    public function getFileError (): ?FileNotFoundException {
        Logger::statusLog('calling '.__FUNCTION__.' of '
            .$this.' returning '.$this->fileError);
        return $this->fileError;
    }

    public function getTreeSectionHeight (): int {
        Logger::statusLog('calling '.__FUNCTION__.' of '
            .$this.' returning '.$this->treeSectionHeight);
        return $this->treeSectionHeight;
    }

    //==================================================
    //getters with actual logic (:OOOOOOOOO)

    public function getX (): int {
        Logger::statusLog('calling '.__FUNCTION__.' of '.$this);
        $retX = $this->x + self::SAFETY_SPACE;
        Logger::statusLog('returning '.$retX.' in getX call of '.$this);
        return $retX;
    }

    public function getMiddleX (): int {
        Logger::statusLog('calling '.__FUNCTION__.' of '.$this);
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
            Logger::statusLog($this.' doesn\'t learn via event => returning normal x');
            $normalX = $this->getX();
            Logger::statusLog('returning normal x '
                .$normalX.' in getIconX call on '.$this);
            return $normalX;
        }
        Logger::statusLog($this.' learns via event => calculating indentation');
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
       	Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        /*depending on which is wider, the icon or
        the text have to be indented a bit*/
        if ($otherWidth > $targetWidth) {
            Logger::statusLog('width of target is smaller calculating indentation');
            $indentation = ($otherWidth - $targetWidth) / 2;
            Logger::statusLog('returning indentation '
                .$indentation.' in getPartXOffset call on '.$this);
            return $indentation;
        }
        Logger::statusLog('width of target is greater or equal => returning 0 indentation');
        return 0;
    }

    public function getY (): int {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        $retY = $this->y + self::SAFETY_SPACE;
        Logger::statusLog('returning y '.$retY.' in getY call on '.$this);
        return $retY;
    }

    public function getMiddleY (): int {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        $middleY = $this->getY() + $this->getHeight() / 2;
        Logger::statusLog('returning middle y '
            .$middleY.' in getMiddleY call on '.$this);
        return $middleY;
    }

    public function getWidth (): int {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
        if (!$this->learnsByEvent) {
            Logger::statusLog($this.' doesn\'t learn via event '
                .'=> returning icon width '.$this->iconWidth);
            return $this->iconWidth;
        }
        Logger::statusLog($this.' does learn via event => returning '
            .'larger one of icon width and event text width');
        $retWidth = max($this->iconWidth, self::EVENT_TEXT_WIDTH);
        Logger::statusLog('returning width '.$retWidth);
        return $retWidth;
    }

    public function getHeight (): int {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this);
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