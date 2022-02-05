<?php
require_once __DIR__.'/../Pkmn.php';
require_once __DIR__.'/../exceptions/FileNotFoundException.php';
require_once __DIR__.'/../Logger.php';

use MediaWiki\MediaWikiServices;

class FrontendPkmn extends Pkmn {
    private bool $learnsByEvent;
    private bool $learnsByOldGen;
    private Array $successors = [];
    private bool $isRoot;

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
        $this->isRoot = $breedingTreeNode->getIsRoot();

        foreach ($breedingTreeNode->getSuccessors() as $successorTreeNode) {
            $successorFrontendObj = new FrontendPkmn($successorTreeNode);
            $this->addSuccessor($successorFrontendObj);
        }
    }

    /**
     * Calculates the depth of the breeding tree by going every path and counting the longest parts together.
     * @return int
     */
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

    /**
     * Just calls functions
     *  * setIconData
     *  * calcTreeSectionHeights
     *  * calcYCords
     *  * calcXCoords
     */
    public function setTreeIconsAndCoordinates () {
        $this->setIconData();

        $this->calcTreeSectionHeights();
        $this->calcYCoords(0);

        $this->calcXCoords();
    }

    /**
     * Calculates recursively the y coordinate of every node by using the sum
     * of the tree section heights (tree section height of the node itself)
     * of its successors and placing the node in the middle of its tree section.
     * For branch ends this just sets the node at the offset.
     * 
     * todo this can be probably fused with calcTreeSectionHeights;
     * 
     * @param int $sectionOffset - y offset of this tree section
     * 
     * @return int height of this tree section
     */
    private function calcYCoords (int $sectionOffset): int {
        $yCoord = $sectionOffset;
        if ($this->hasSuccessors()) {
            $yCoord += $this->treeSectionHeight / 2 - $this->getIconHeight() / 2;
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

    /**
     * Calculates x coordinates of every node by traversing through the
     * tree layers und counting the deepness. The margin betweent each
     * pkmn column is saved in Constants::PKMN_MARGIN_HORI.
     * @param int $deepness
     */
    private function calcXCoords (int $deepness = 0) {
        //- getIconWidth / 2 is for centering the icons
        $this->x = $deepness * Constants::PKMN_MARGIN_HORIZONTAL - $this->getIconWidth() / 2;
        Logger::statusLog('calculated x coordinate of '.$this);
        foreach ($this->successors as $successor) {
            $successor->calcXCoords($deepness + 1);
        }
    }

    /**
     * Tries to load the icon files for every node and sets its properties
     * or the FileNotFoundException, depending on success.
     */
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


    /**
     * Recursively calculates the height of each tree section.
     * A tree section is a node with its successors.
     * The heights are calculated by using the sum of the succesors or
     * by using the icon height for branch ends.
     * 
     * todo this can probably be fused with calcYCoords
     * 
     * @return int
     */
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

    /**
     * Tries to load and return the icon file object of $pkmnId and throws a
     * FileNotFoundException if it fails.
     * 
     * @param string $pkmnId - pkmn id as used in PokeWiki (is a string because special forms have character postfixes)
     * 
     * @return File
	 * 
	 * @throws FileNotFoundException
     */
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

    public function getX (): int {
        return $this->x;
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
        return $this->y;
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

    public function getIsRoot (): bool {
        return $this->isRoot;
    }

    /**
     * @return string - FrontendPkmn:<pkmn name>;(<x>;<y>);<branch position>;;
     */
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