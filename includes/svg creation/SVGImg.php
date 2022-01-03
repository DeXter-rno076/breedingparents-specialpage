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

        $this->x = $frontendPkmn->getX();
        $this->y = $frontendPkmn->getY();
        $this->width = $frontendPkmn->getIconWidth();
        $this->height = $frontendPkmn->getIconHeight();

        $this->href = $frontendPkmn->getIconUrl();
        if ($frontendPkmn->getLearnsByEvent()) {
            $this->addEventMarker($frontendPkmn->getName());
        } else if ($frontendPkmn->getLearnsByOldGen()) {
            $this->addOldGenMarker($frontendPkmn->getName());
        }

        Logger::statusLog('created '.$this);
    }

    private function addEventMarker (string $temp_pkmnName) {
        //todo
        Constants::out($temp_pkmnName.' learns via event');
    }

    private function addOldGenMarker (string $temp_pkmnName) {
        //todo
        Constants::out($temp_pkmnName.' learns via old gen');
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