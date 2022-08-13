<?php
require_once 'Track.php';
require_once __DIR__.'/../visual_creation/VisualPreparationNode.php';
require_once __DIR__.'/../visual_creation/VisualPreparationSubtree.php';
require_once __DIR__.'/../HTMLElement.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../visual_creation/json/JSONRoot.php';
require_once __DIR__.'/../Logger.php';

class SVGCreationTrack extends Track {
    private $frontendRoot;

    public function __construct (VisualPreparationSubtree $frontendRoot) {
        $this->frontendRoot = $frontendRoot;
    }

    public function passOn (): string {
        $svgMap = $this->createSVGMapDiv();
        $svgStructure = $this->createVisualStructure();
        $this->addVisualStructuresToOutput($svgMap, $svgStructure);

        return 'all ok';
    }

    private function createSVGMapDiv (): HTMLElement {
        $mapDiv = new HTMLElement('div', [
            'id' => 'breedingChainsSVGMap',
        ]);
        return $mapDiv;
    }

    private function createVisualStructure (): array {
        Logger::statusLog('CREATING VISUAL STRUCTURE');
        $timeStart = hrtime(true);

        $visualRoot = new JSONRoot($this->frontendRoot, Constants::UNUSED_GROUP_ID);
        $compiledVisualStructure = $visualRoot->compile();

        $timeEnd = hrtime(true);
        $timeDiff = ($timeEnd - $timeStart) / 1000000000;
        Logger::outputDebugMessage('visual creation needed: '.$timeDiff.'s');

        return $compiledVisualStructure;
    }

    private function addVisualStructuresToOutput (HTMLElement $map, array $visualStructure) {
        $map->addToOutput();
        Constants::$centralOutputPageInstance->addJsConfigVars([
            'breedingchains-visual-structure' => $visualStructure
        ]);
    }
}