<?php
require_once __DIR__.'/../exceptions/FileNotFoundException.php';
require_once __DIR__.'/../output_messages/ErrorMessage.php';
require_once __DIR__.'/../tree_creation/BreedingTreeNode.php';
require_once __DIR__.'/../tree_creation/PkmnData.php';
require_once __DIR__.'/../tree_creation/PkmnTreeRoot.php';
require_once __DIR__.'/../Pkmn.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../Constants.php';

use MediaWiki\MediaWikiServices;

class VisualNode extends Pkmn {
    private $isRoot;

    private $x;
    private $y;

    private $iconName;
    private $iconUrl = '';
    private $iconWidth = 0;
    private $iconHeight = 0;
    private $fileError = null;

    private $groupId;
    private $learnabilityCode;
    private $correctlyWrittenName;

    public function __construct (BreedingTreeNode $breedingTreeNode) {
        parent::__construct($breedingTreeNode->getName());

        $this->isRoot = $breedingTreeNode instanceof PkmnTreeRoot;
        $this->groupId = Constants::generateGroupId();
        $this->learnabilityCode = $breedingTreeNode->getLearnabilityStatus()->buildLearnabilityCode();
        $this->iconName = $breedingTreeNode->buildIconName();
        $this->correctlyWrittenName = $breedingTreeNode->getCorrectlyWrittenName();
    }

    public function setIconData () {
        try {
            $this->tryLoadAndSetIconData();
        } catch (FileNotFoundException $e) {
            $this->setFileError($e);
        }
    }

    private function tryLoadAndSetIconData () {
        $iconFileObj = VisualNode::getIcon($this->iconName);
        Logger::statusLog('icon file for '.$this.' successfully loaded');

        $this->iconUrl = $iconFileObj->getUrl();
        $this->iconWidth = $iconFileObj->getWidth();
        $this->iconHeight = $iconFileObj->getHeight();
    }

    public static function getIcon (string $fileURL): File {
        $fileObj = MediaWikiServices::getInstance()->getRepoGroup()->findFile($fileURL);

        if ($fileObj === false) {
            throw new FileNotFoundException($fileURL);
        }

        return $fileObj;
    }

    private function setFileError (FileNotFoundException $e) {
        Logger::statusLog('couldnt load file obj of '.$this);
        $this->fileError = $e;
    }

    public function calculateDiagonal (): int {
        return Constants::SVG_CIRCLE_DIAMETER;
    }

    public function calcAndSetCenteredXCoordinate (int $deepness): int {
        $this->x = $deepness * Constants::PKMN_MARGIN_HORIZONTAL;
        Logger::statusLog('calculated x = '.$this->x.', for '.$this);
        return $this->x;
    }

    public function getDisplayEventMarker (): bool {
        return $this->str_contains($this->learnabilityCode, 'e');
    }

    private function str_contains (string $haystack, string $needle): bool {
        return strpos($haystack, $needle) !== false;
    }

    public function getDisplayOldGenMarker (): bool {
        return $this->str_contains($this->learnabilityCode, 'o');
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
        return $this->getX() + $this->calcWidth()/2;
    }

    public function getIconX (): int {
        return $this->getMiddleX() - $this->iconWidth/2;
    }

    public function setY (int $y) {
        $this->y = $y;
    }

    public function getTopY (): int {
        return $this->y;
    }

    public function getMiddleY (): int {
        $middleY = $this->y + $this->calcHeight()/2;
        return $middleY;
    }

    public function getBottomY (): int {
        return $this->y + $this->calcHeight();
    }

    public function getIconY (): int {
        return $this->getMiddleY() - $this->iconHeight/2;
    }

    public function calcHeight (): int {
        //todo replace magic number with constant
        $pureHeight = $this->calculateDiagonal() + 10;
        return $pureHeight + 2*Constants::SVG_CIRCLE_MARGIN;
    }

    public function calcWidth (): int {
        return $this->calcHeight();
    }

    public function isRoot (): bool {
        return $this->isRoot;
    }

    public function getGroupId (): int {
        return $this->groupId;
    }

    public function getLearnabilityCode (): string {
        return $this->learnabilityCode;
    }

    public function getCorrectlyWrittenName (): string {
        return $this->correctlyWrittenName;
    }

    public function getArticleLink (): string {
        if (Constants::isPkmn($this->getName())) {
            try {
                $pkmnData = new PkmnData($this->getName());
                $linkSuperPage = $pkmnData->getArticleLinkSuperPageName();
                $linkName = Constants::i18nMsg('breedingchains-learnsetpage-link',
                    $linkSuperPage, Constants::$targetGenNumber);
                return $linkName;
            } catch (Exception $e) {
                $eMessage = new ErrorMessage($e);
                $eMessage->output();
            }
        } else {
            return $this->getName();
        }
    }

    /**
     * @return string - VisualNode:<pkmn name>;(<x>;<y>);<branch position>;;
     */
    public function getLogInfo (): string {
        $msg = 'VisualNode:\'\'\''.$this->name.'\'\'\';('
            .(isset($this->x) ? $this->x : '-').';'
            .(isset($this->y) ? $this->y : '-').')';
        $msg .= ';;';
        return $msg;
    }
}