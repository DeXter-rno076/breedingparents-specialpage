<?php
require_once 'Track.php';
require_once __DIR__.'/../Constants.php';
require_once 'PreBreedingTreeCreationCheckpoint.php';

class ExternalDataLoadingTrack extends Track {
	public function passOn (): string {
		$this->loadExternalJSON();
		$preTreeCreationCheckpoint = new PreBreedingTreeCreationCheckpoint();
		return $preTreeCreationCheckpoint->passOn(); 
	}

	private function loadExternalJSON () {
		$this->loadExternalJSONPkmnData();

		$eggGroupPageName = 'MediaWiki:BreedingChains/Gen'
			.Constants::$targetGenNumber.'/egg groups '.Constants::$targetGameString.'.json';
		Constants::$externalEggGroupsJSON = Constants::$centralSpecialPageInstance->getWikiPageContent($eggGroupPageName);	
	}

	private function loadExternalJSONPkmnData () {
		$specialPageInstance = Constants::$centralSpecialPageInstance;
	
		$genCommonsPageTitleScheme = 'MediaWiki:BreedingChains/Gen'.Constants::$targetGenNumber.'/commons ##INDEX##.json';
		Constants::$externalPkmnGenCommons = $specialPageInstance->loadSplitExternalJSON($genCommonsPageTitleScheme);
	
		$gameDiffsPageTitleScheme = 'MediaWiki:BreedingChains/Gen'.Constants::$targetGenNumber
			.'/diffs '.Constants::$targetGameString.' ##INDEX##.json';
		Constants::$externalPkmnGameDiffs = $specialPageInstance->loadSplitExternalJSON($gameDiffsPageTitleScheme);
	}
}