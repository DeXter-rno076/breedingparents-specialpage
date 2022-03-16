<?php
require_once __DIR__.'/../HTMLElement.php';
require_once 'SVGLine.php';

class SVGPkmnConnection {
	private $line;
	private $text = null;
	private $groupId;

	public function __construct (SVGLine $line, int $groupId, string $text = null) {
		$this->line = $line;
		$this->groupId = $groupId;
		if (!is_null($text)) {
			$this->text = $this->createSVGTextOverLine($text);
		}
	}

	public static function constructWithoutText (int $x1, int $y1, int $x2, int $y2, int $groupId): SVGPkmnConnection {
		$line = new SVGLine($x1, $y1, $x2, $y2, $groupId);
		return new SVGPkmnConnection($line, $groupId);
	}

	public function createSVGTextOverLine (string $text): SVGText {
		$textX = $this->calculateTextX($text);

		$textY = $this->calculateTextY();

		if ($this->textIsLongerThanLine($textX)) {
			//todo
			Logger::wlog('pkmn connection line with text '.$text.' is probably shorter than the text');
			//use substr_replace($text, '\n' | '<br />', length/2, 0) to insert line breaks
		}

		return new SVGText($textX, $textY, $text, $this->groupId);
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
		return $textX < $this->line->getLeftBorder() + Constants::SVG_LINE_WIDTH;
	}

	private function calculateTextY (): int {
		return $this->line->getHeight() - Constants::SVG_TEXT_LINE_MARGIN;
	}

	public function toHTMLElements (int $xOffset, int $yOffset): array {
		$retArray = [
			$this->line->toHTML($xOffset, $yOffset),
		];
		if (!is_null($this->text)) {
			$retArray[] = $this->text->toHTML($xOffset, $yOffset);
		}

		return $retArray;
	}

	public function getLogInfo (): string {
		return '\'\'\'SVGPkmnConnection\'\'\':('
			.$this->line->getLogInfo().'),('.$this->text->getLogInfo().');;';
	}
}