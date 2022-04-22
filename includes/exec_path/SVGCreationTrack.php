<?php
require_once 'Track.php';
require_once __DIR__.'/../svg_creation/FrontendPkmn.php';
require_once __DIR__.'/../HTMLElement.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../svg_creation/SVGTag.php';
require_once __DIR__.'/../Logger.php';

class SVGCreationTrack extends Track {
	private $frontendRoot;

	public function __construct (FrontendPkmn $frontendRoot) {
		$this->frontendRoot = $frontendRoot;
	}

	public function passOn (): string {
		$svgMap = $this->createSVGMapDiv();
		$svgStructure = $this->createSVGStructure();
		$this->addVisualStructuresToOutput($svgMap, $svgStructure);

		return 'all ok';
	}

	private function createSVGMapDiv (): HTMLElement {
		$mapDiv = new HTMLElement('div', [
			'id' => 'breedingChainsSVGMap',
		]);
		return $mapDiv;
	}

	private function createSVGStructure (): HTMLElement {
		Logger::statusLog('CREATING SVG STRUCTURE');
		$timeStart = hrtime(true);

		$svgRoot = new SVGTag($this->frontendRoot, Constants::UNUSED_GROUP_ID);
		$svgStructureInHTML = $svgRoot->toHTML();

		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		Logger::outputDebugMessage('svg creation needed: '.$timeDiff.'s');

		return $svgStructureInHTML;
	}

	private function addVisualStructuresToOutput (HTMLElement $svgMapDiv, HTMLElement $svgStructure) {
		$this->addMarkerExplanations();
		$svgMapDiv->addToOutput();
		$svgStructure->addToOutput();
	}

	private function addMarkerExplanations () {
		require_once __DIR__.'/../markerExamples.php';
		$markerExamplesTable->addToOutput();
	}
}