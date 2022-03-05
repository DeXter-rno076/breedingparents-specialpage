<?php
require_once 'Constants.php';

/**
 * the Html class from MediaWiki has Html::element to create elements with children
 * but this function takes the inner content only as a plain string -> no string cleaning
 * so I made a half wrapper, half own class, that solves the issue and makes it generally handier
 */
class HTMLElement {
	private string $tagName;
	private array $attributes;
	//innerContent is a mix of HTMLElements and strings
	private array $innerContent;

	public function __construct (
		string $tagName,
		array $attribs = [],
		array $innerElements = []
	) {
		$this->tagName = $tagName;
		$this->attributes = $attribs;
		$this->innerContent = $innerElements;
	}

	public function setAttribute (string $name, string $value) {
		$this->attributes[$name] = $value;
	}

	public function addInnerElement (HTMLElement $el) {
		array_push($this->innerContent, $el);
	}

	public function addInnerString (string $txt) {
		$cleanText = htmlentities($txt);
		array_push($this->innerContent, $cleanText);
	}

	private function buildHTML (): string {
		if ($this->hasInnerContent()) {
			return $this->buildHTMLWithInnerContent();
		} else {
			return $this->buildHTMLOfEmptyTag();
		}
	}

	public function hasInnerContent (): bool {
		return count($this->innerContent) > 0;
	}

	private function buildHTMLWithInnerContent (): string {
		$start = Html::openElement($this->tagName, $this->attributes);
		$middle = $this->buildInnerContent();
		$end = Html::closeElement($this->tagName);

		return $start.$middle.$end;
	}

	private function buildInnerContent (): string {
		$innerContentString = '';

		foreach ($this->innerContent as $el) {
			/*to both support nested elements and plain strings
			$el is converted to string and toString for HTMLElement is overwritten*/
			$innerContentString .= (string) $el;
		}

		return $innerContentString;
	}

	private function buildHTMLOfEmptyTag () {
		return Html::element($this->tagName, $this->attributes, '');
	}

	public function addToOutput () {
		Constants::$centralOutputPageInstance->addHTML($this->buildHTML());
	}

	public function __toString (): string {
		return $this->buildHTML();
	}
}