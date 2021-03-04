<?php
class SVGHandler {
	private $objData = null;
	private $maxDeepness = -1;
	private $svgTag = '';
	private $output = null;

	public function __construct ($objData, $maxDeepness, $output, $svgHeight) {
		$this->objData = $objData;
		$this->maxDeepness = $maxDeepness;
		$this->output = $output;
		$this->svgTag = '<svg id="breedingParentsSVG" width="100%" height="'.$svgHeight.'">\n';
	}

	public function createSVG ($output) {
		createSVGElements($this->objData);

		$this->svgTag += '</svg>';
		$this->output->addHTML($this->svgTag);
	}

	private function createLine ($startX, $startY, $endX, $endY) {
		$svgElement = '<line x1="'.$startX.'%" y1="'.$startY.'" x2="'.$endX.'%" y2="'.$endY.'" />';
		return $svgElement;
	}
}
?>