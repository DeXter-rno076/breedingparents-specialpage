<?php
require_once __DIR__.'/../Logger.php';
require_once 'VisualElement.php';

abstract class VisualText extends VisualElement {
    protected $x;
    protected $y;
    protected $text;

    public function __construct (int $x, int $y, string $text, int $groupId) {
        parent::__construct('text', $groupId);

        $this->x = $x;
        $this->y = $y;
        $this->text = $text;

        Logger::statusLog('created '.$this);
    }
}