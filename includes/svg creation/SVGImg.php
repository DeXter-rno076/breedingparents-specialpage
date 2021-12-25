<?php
require_once __DIR__.'/../Logger.php';
require_once 'SVGElement.php';
require_once 'FrontendPkmn.php';

class SVGImg extends SVGElement { 
    private int $x;
    private int $y;
    private int $width;
    private int $height;
    private string $href;

    public function __construct (FrontendPkmn $frontendPkmn) {
        parent::__construct('image');
        Logger::statusLog('creating SVGImg instance for '.$frontendPkmn);

        $this->x = $frontendPkmn->getX();
        $this->y = $frontendPkmn->getY();
        $this->width = $frontendPkmn->getIconWidth();
        $this->height = $frontendPkmn->getIconHeight();

        $this->href = $frontendPkmn->getIconUrl();
        if ($frontendPkmn->getLearnsByEvent()) {
            $this->addEventMarker();
        } else if ($frontendPkmn->getLearnsByOldGen()) {
            $this->addOldGenMarker();
        }

        Logger::statusLog('created '.$this);
    }

    private function addEventMarker () {
        //todo
    }

    private function addOldGenMarker () {
        //todo
    }

    public function toHTMLString (): string {
        return '<image x="'.$this->x.'" y="'.$this->y.
        '" width="'.$this->width.'" height="'.$this->height.
        '" xlink:href="'.$this->href.'" />';
    }

    public function getLogInfo (): string {
        return 'SVGImg:('.$this->x.';'.$this->y.');href='.$this->href.';;';
    }
}