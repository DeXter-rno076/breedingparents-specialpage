<?php
require_once __DIR__.'/../Logger.php';
require_once 'SVGElement.php';
require_once 'FrontendPkmn.php';
require_once 'SVGCircle.php';
require_once 'SVGRectangle.php';

class SVGImg extends SVGElement { 
    private int $x;
    private int $y;
    private int $width;
    private int $height;
    private string $href;
    private ?SVGElement $marker = null;

    private FrontendPkmn $frontendPkmn;

    public function __construct (FrontendPkmn $frontendPkmn) {
        parent::__construct('image');

        $this->frontendPkmn = $frontendPkmn;

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
        $x = $this->frontendPkmn->getX();
        $y = $this->frontendPkmn->getY();
        $width = $this->frontendPkmn->getWidth();
        $height = $this->frontendPkmn->getHeight();

        $eventMarker = new SVGRectangle(
            $x - Constants::SVG_RECT_PADDING,
            $y - Constants::SVG_RECT_PADDING,
            $width + 2 * Constants::SVG_RECT_PADDING,
            $height + 2 * Constants::SVG_RECT_PADDING
        );

        $this->marker = $eventMarker;
    }

    private function addOldGenMarker () {
        $middleX = $this->frontendPkmn->getMiddleX();
        $middleY = $this->frontendPkmn->getMiddleY();
        $width = $this->frontendPkmn->getWidth();
        
        $oldGenMarker = new SVGCircle($middleX, $middleY, $width / 2 + Constants::SVG_CIRCLE_MARGIN);
        $this->marker = $oldGenMarker;
    }

    public function toHTMLString (): string {
        $svgCode = '';
        if (!is_null($this->marker)) {
            $svgCode .= $this->marker->toHTMLString();
        }
        $svgCode .= '<image x="'.$this->x.'" y="'.$this->y.
        '" width="'.$this->width.'" height="'.$this->height.
        '" xlink:href="'.$this->href.'" />';

        return $svgCode;
    }

    public function getLogInfo (): string {
        return 'SVGImg:('.$this->x.';'.$this->y.');href='.$this->href.';;';
    }
}