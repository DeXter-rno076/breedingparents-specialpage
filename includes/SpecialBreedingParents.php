<?php
//todo find a solution for output of validation methods

require_once 'Backend/BackendHandler.php';
require_once 'Frontend/FrontendHandler.php';
require_once 'Constants.php';

class SpecialBreedingParents extends SpecialPage {
	public function __construct () {
		parent::__construct('Zuchteltern');
	}

	public function execute ($args) {
		$this->setHeaders();//seems like a must have
		$this->getOutput()->setPageTitle('Spezial:Zuchteltern');
		$this->addForms();
	}

	public function processStuff ($data, $form) {
		//todo check whether pkmn is unbreebable

		Constants::$targetGen = $data['genInput'];
		Constants::$targetMove = $data['moveInput'];
		Constants::$targetPkmn = $data['pkmnInput'];
		Constants::$out = $this->getOutput();

		$this->getData();

		$backendHandler = new BackendHandler();
		$breedingTree = $backendHandler->createBreedingTree();

		$frontendHandler = new FrontendHandler($breedingTree);
		$frontendHandler->addGraficOutput();

		return Status::newGood();
	}

	private function addForms () {
		require_once 'formDescriptor.php';
	
		$form = HTMLForm::factory('inline', $formDescriptor, $this->getContext());
		$form->setMethod('get');
		$form->setSubmitCallback([$this, 'processStuff']);
		$form->setSubmitText('do it');//todo text not final
		$form->prepareForm();

		$form->displayForm('');
		$form->trySubmit();
	}
	
	//has to be public
	public function validatePkmn ($value, $allData) {
		if ($value === '' || $value === null) {
			return true;
		}

		//these are all characters that are used in pkmn names
		$regex = '/[^a-zA-Zßäéü\-♂♀2:]/';
		if (preg_match($regex, $value)) {
			Constants::out('pkmn name is evil >:(');
			return 'Invalid character in the Pokémon name';
		}
	
		return true;
	}
	
	//has to be public
	public function validateMove ($value, $allData) {
		if ($value === '' || $value === null) {
			return true;
		}

		//these are all characters that are used in move names
		$regex = '/[^a-zA-ZÜßäöü\- 2]/';
		if (preg_match($regex, $value)) {
			Constants::out('move name is evil >:(');
			return 'Invalid character in the move name';
		}
		
		return true;
	}

	//has to be public
	public function validateGen ($value, $allData) {
		if ($value === '' || $value === null) {
			return true;
		}
		
		if (!is_numeric($value)) {
			Constants::out('gen is evil >:(');
			return 'Invalid gen input';
		}

		return true;
	}

	private function getData () {
		$gen = Constants::$targetGen;
		Constants::$pkmnData = $this->getPkmnData($gen);

		$blacklistPageName = 'MediaWiki:Zuchteltern/Gen'.$gen.'/pkmn-blacklist.json';
		Constants::$unbreedable = $this->getWikiPageContent($blacklistPageName);
		
		$eggGroupPageName = 'MediaWiki:Zuchteltern/Gen'.$gen.'/egg-groups.json';
		Constants::$eggGroups = $this->getWikiPageContent($eggGroupPageName);
	}

	private function getPkmnData (String $gen) : StdClass {
		$pkmnDataArr = [];
		$pageData = null;
		$pageIndex = 1;

		do {
			$pkmnDataPageName = 'MediaWiki:Zuchteltern/Gen'.$gen.'/pkmn-data'.$pageIndex.'.json';
			$pageData = $this->getWikiPageContent($pkmnDataPageName);

			$pageDataArray = (array) $pageData;
			$pkmnDataArr = array_merge($pkmnDataArr, $pageDataArray);
			
			$pageIndex++;
		} while (isset($pageData->continue));

		$pkmnDataObj = (object) $pkmnDataArr;

		return $pkmnDataObj;
	}
	
	//original code written by Buo (thanks ^^)
	//returns JSON objects and arrays -> needs 2 return types which needs php 8
	private function getWikiPageContent (String $name) {
		$title = Title::newFromText($name);
		$rev = Revision::newFromTitle($title);

		if (is_null($rev)) {
			throw new Exception('wiki page '.$name.' not found');
		}

		$data = $rev->getContent()->getNativeData();

		return json_decode($data);;
	}

	//===========================================================
	//debugging stuff

	private function debugConsole (String $msg) {
		echo '<script>console.log("'.$msg.'")</script>';
	}
}