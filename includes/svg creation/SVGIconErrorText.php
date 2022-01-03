<?php
require_once __DIR__.'/../Logger.php';
require_once 'SVGElement.php';

class SVGIconErrorText extends SVGElement {
    private int $x;
    private int $y;
    private string $pkmnName;

    public function __construct (FrontendPkmn $pkmn) {
        parent::__construct('text');
        $this->x = $pkmn->getX();
        $this->y = $pkmn->getY();
        $this->pkmnName = $pkmn->getName();

        Logger::statusLog('created '.$this);
    }

    public function toHTMLString (): string {
        $x = $this->x;
        $y = $this->y;
        return '<text x="'.$x.'" y="'.$y.'">'.	
        '<tspan x="'.$x.'" y="'.$y.'">Oh, das hätte nicht passieren sollen :\'(</tspan>'.
        '<tspan x="'.$x.'" y="'.($y + 20).'">Melde das bitte auf unserem</tspan>'.
        '<tspan x="'.$x.'" y="'.($y + 40).'">Discordserver oder in der'.
        Constants::$auskunftLink.' ^^</tspan>'.
        '<tspan x="'.$x.'" y="'.($y + 60).'">Fehler beim Laden'.
        ' von "'.$this->pkmnName.'
        "</tspan></text>';
    }

    public function getLogInfo (): string {
        return 'SVGIconErrorText:pkmn='.$this->pkmnName.';('.$this->x.';'.$this->y.');;';
    }
}