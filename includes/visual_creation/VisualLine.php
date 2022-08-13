<?php
require_once __DIR__.'/../Logger.php';
require_once 'VisualElement.php';

abstract class VisualLine extends VisualElement {
    protected $x1;
    protected $y1;
    protected $x2;
    protected $y2;

    public function __construct (int $x1, int $y1, int $x2, int $y2, int $groupId) {
        parent::__construct('line', $groupId);

        $this->x1 = $x1;
        $this->y1 = $y1;
        $this->x2 = $x2;
        $this->y2 = $y2;

        Logger::statusLog('created '.$this);
    }

    public function getLeftBorder (): int {
        return $this->x1;
    }

    public function getRightBorder (): int {
        return $this->x2;
    }

    public function getHeight (): int {
        if ($this->y1 !== $this->y2) {
            Logger::wlog('called getHeight on non horizontal line; y1: '.$this->y1.', y2: '.$this->y2);
        }
        return $this->y1;
    }
}