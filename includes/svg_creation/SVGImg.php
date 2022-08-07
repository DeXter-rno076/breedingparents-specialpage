<?php
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../HTMLElement.php';

require_once 'SVGElement.php';
require_once 'VisualNode.php';

class SVGImg extends SVGElement {
	private $x;
	private $y;
	private $width;
	private $height;

    private $href;

    private $learnabilityCode;

	public function __construct (VisualNode $visualNode) {
		parent::__construct('image', $visualNode->getGroupId());

		$this->x = $visualNode->getX();
		$this->y = $visualNode->getY();
		$this->width = $visualNode->getIconWidth();
		$this->height = $visualNode->getIconHeight();

		$this->href = $visualNode->getIconUrl();

        $this->learnabilityCode = $visualNode->getLearnabilityCode();
        Logger::statusLog('learnability code for '.$visualNode->getName().': '.$this->learnabilityCode);

		Logger::statusLog('created '.$this);
	}

	public function toHTML (int $xOffset, int $yOffset): HTMLElement {
		return new HTMLElement('image', [
			'x' => $this->x + $xOffset,
			'y' => $this->y + $yOffset,
			'width' => $this->width,
			'height' => $this->height,
			'xlink:href' => $this->href,
			'groupid' => $this->groupId,
            'learnability' => $this->learnabilityCode
		]);
	}

	public function getLogInfo (): string {
		return '\'\'\'SVGImg\'\'\':('.$this->x.';'.$this->y.');href='.$this->href.';;';
	}
}