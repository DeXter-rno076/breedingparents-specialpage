<?php
require_once 'Constants.php';

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

    private function buildInnerContent (): string {
        $innerContentString = '';

        foreach ($this->innerContent as $el) {
            /*to both support nested elements and plain strings
            $el is converted to string and toString for HTMLElement is overwritten*/
            $innerContentString .= (string) $el;
        }

        return $innerContentString;
    }

    private function buildHTML (): string {
        $result = '';
        if ($this->hasInnerContent()) {
            $start = Html::openElement($this->tagName, $this->attributes);
            $middle = $this->buildInnerContent();
            $end = Html::closeElement($this->tagName);

            $result = $start.$middle.$end;
        } else {
            $result = Html::element($this->tagName, $this->attributes, '');
        }

        return $result;
    }

    public function hasInnerContent (): bool {
        return count($this->innerContent) > 0;
    }

    public function addToOutput () {
        Constants::$centralOutputPageInstance->addHTML($this->buildHTML());
    }

    public function __toString (): string {
        return $this->buildHTML();
    }
}