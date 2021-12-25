<?php
require_once __DIR__.'/../Logger.php';

class FileNotFoundException extends Exception {
    private string $msg;
    private string $pkmnId;
    
    public function __construct (string $pkmnId, Throwable $previous = null) {
        Logger::statusLog('constructing FileNotFoundException instance of '.$pkmnId);
        $this->msg = 'couldn\'t load pkmn icon of ' + $pkmnId;
        $this->pkmnId = $pkmnId;
        parent::__construct($this->msg, 0, $previous);
    }

    public function __toString (): string {
        return 'FileNotFoundException: '.$this->msg;
    }

    public function getPkmnId (): string {
        Logger::statusLog('calling '.__FUNCTION__.' on '
            .$this.', returning '.$this->pkmnId);
        return $this->pkmnId;
    }
}