<?php
require_once __DIR__.'/../Logger.php';

class InvalidStateException extends Exception {
    private $msg;

    public function __construct (string $info, Throwable $previous = null) {
        $this->msg = 'reached invalid state of special page: '.$info;
        parent::__construct($this->msg, 0, $previous);

        Logger::elog($this->__toString());
    }

    public function __toString (): string {
        return 'InvalidStateException: '.$this->msg;
    }
}