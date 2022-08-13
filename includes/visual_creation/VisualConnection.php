<?php
require_once 'VisualLine.php';
require_once 'VisualText.php';
require_once 'VisualComplex.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../Logger.php';

abstract class VisualConnection extends VisualComplex {
    protected $line;
    protected $text = null;
    protected $groupId;

    protected function __construct (VisualLine $line, int $groupId, string $text = null) {
        $this->line = $line;
        $this->groupId = $groupId;
        if (!is_null($text)) {
            $this->text = $this->createTextOverLine($text);
        }
    }

    public function createTextOverLine (string $text): VisualText {
        $textX = $this->calculateTextX($text);

        $textY = $this->calculateTextY();

        if ($this->textIsLongerThanLine($textX)) {
            //todo
            Logger::wlog('pkmn connection line with text '.$text.' is probably shorter than the text');
            //use substr_replace($text, '\n' | '<br />', length/2, 0) to insert line breaks
        }

        return $this->instantiateText($textX, $textY, $text, $this->groupId);
    }

    private function calculateTextX (string $text): int {
        //mb_strwidth is meant for determining string widths but it's php 8
        //factor 6 was determined by trying out strings
        $approximateStringWidth = strlen($text) * 6;

        $leftBorder = $this->line->getLeftBorder();
        $rightBorder = $this->line->getRightBorder();
        $connectionLength = abs($rightBorder - $leftBorder);

        $textXDiffToLineStart = ($connectionLength - $approximateStringWidth) / 2;
        $textX = $leftBorder + $textXDiffToLineStart;
        return $textX;
    }

    private function textIsLongerThanLine (int $textX): bool {
        return $textX < $this->line->getLeftBorder() + Constants::VISUAL_LINE_WIDTH;
    }

    private function calculateTextY (): int {
        return $this->line->getHeight() - Constants::VISUAL_TEXT_LINE_MARGIN;
    }

    protected abstract function instantiateText (int $textX, int $textY, string $text, int $groupId): VisualText;
}