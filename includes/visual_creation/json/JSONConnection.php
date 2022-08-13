<?php
require_once __DIR__.'/../VisualConnection.php';
require_once 'JSONLine.php';
require_once 'JSONText.php';

class JSONConnection extends VisualConnection {
    public function __construct (JSONLine $line, int $groupId, string $text = null) {
        parent::__construct($line, $groupId, $text);
    }

    protected function instantiateText (int $textX, int $textY, string $text, int $groupId): VisualText {
        return new JSONText($textX, $textY, $text, $groupId);
    }

    public function compile (int $xOffset, int $yOffset): array {
        $retArray = [
            $this->line->compile($xOffset, $yOffset),
        ];
        if (!is_null($this->text)) {
            $retArray[] = $this->text->compile($xOffset, $yOffset);
        }

        return $retArray;
    }

    public function getLogInfo (): string {
        return '\'\'\'JSONConnection\'\'\':('
            .$this->line->getLogInfo().'),('.$this->text->getLogInfo().');;';
    }
}