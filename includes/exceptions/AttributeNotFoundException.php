<?php
require_once __DIR__.'/../Logger.php';

class AttributeNotFoundException extends Exception {
    private string $msg;
    private string $pkmnId;
    private string $missingAttribute;

    public function __construct (string $pkmnId,
            string $attr, Throwable $previous = null) {
        $this->msg = $attr.' is missing in data obj of '.$pkmnId;
        $this->pkmnId = $pkmnId;
        $this->missingAttribute = $attr;
        parent::__construct($this->msg, 0, $previous);
    }

    public function __toString (): string {
        return 'AttributeNotFoundException: '.$this->msg;
    }
}