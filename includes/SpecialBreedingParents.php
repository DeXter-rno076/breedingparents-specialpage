<?php 
require "CallbackHandler.php";

class SpecialBreedingParents extends SpecialPage {
	private $pkmnData = null;
	private $unbreedable = null;
	private $eggGroups = null;

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

		$targetGen = $data['genInput'];
		$targetMove = $data['moveInput'];
		$targetPkmn = $data['pkmnInput'];

		$this->getData($targetGen);
		
		$callbackHandler = new CallbackHandler(
			$this->pkmnData,
			$this->eggGroups,
			$this->unbreedable,
			$targetPkmn,
			$targetMove,
			$this->getOutput(),
		);

		//$callbackResult = $callbackHandler->callbackMain();
		
		//add svg tag, add $svgStructure, add frontendStuff.js

		//$this->debugOutput($callbackResult);
		//$this->debugConsole($callbackResult);

		return Status::newGood();
	}

	private function addForms () {
		require 'formDescriptor.php';
	
		$form = HTMLForm::factory('inline', $formDescriptor, $this->getContext());
		$form->setMethod('get');
		$form->setSubmitCallback([$this, 'processStuff']);
		$form->setSubmitText('do it');//todo text not final
		$form->prepareForm();

		if (empty($_GET)) {
			$form->displayForm('');
		} else {
			$form->displayForm($form->trySubmit());
		}
	}
	
	//has to be public
	public function validatePkmn ($value, $allData) {
		if ($value === '' || $value === null) {
			return true;
		}
	
		$regex = '/[^a-zA-Zßäéü\-♂♀2:]/';//these are all characters that are used in pkmn names
		if (preg_match($regex, $value)) {
			return 'Invalid character in the Pokémon name';
		}
	
		return true;
	}
	
	//has to be public
	public function validateMove ( $value, $allData ) {
		if ($value === '' || $value === null) {
			return true;
		}

		$regex = '/[^a-zA-ZÜßäöü\- 2]/';//these are all characters that are used in move names
		if (preg_match($regex, $value)) {
			return 'Invalid character in the move name';
		}
		
		return true;
	}

	private function getData ($gen) {
		$this->pkmnData = $this->getPkmnData($gen);
		$this->unbreedable = $this->getWikiPageContent('MediaWiki:Zuchteltern/Gen'.$gen.'/pkmn-blacklist.json');
		$this->eggGroups = $this->getWikiPageContent('MediaWiki:Zuchteltern/Gen'.$gen.'/egg-groups.json');
	}

	private function getPkmnData ($gen) {
		$pkmnDataArr = [];
		$pageData = null;
		$pageIndex = 1;

		do {
			$pageData = $this->getWikiPageContent('MediaWiki:Zuchteltern/Gen'.$gen.'/pkmn-data'.$pageIndex.'.json');
			$pageDataArray = (array) $pageData;
			$pkmnDataArr = array_merge($pkmnDataArr, $pageDataArray);
			$pageIndex++;
		} while (!is_null($pageData->continue));

		$pkmnDataObj = (object) $pkmnDataArr;

		return $pkmnDataObj;
	}
	
	//original code written by Buo (thanks ^^)
	private function getWikiPageContent ($name) {
		$title = Title::newFromText($name);
		$rev = Revision::newFromTitle($title);

		if (is_null($rev)) {
			throw new Exception('wiki page '.$name.' not found');
		}

        $data = $rev->getContent()->getNativeData();

        return json_decode($data);
	}

	//===========================================================
	//debugging stuff

	private function debugOutput ($msg) {
		$this->getOutput()->addHTML($msg);
	}

	private function debugConsole ($msg) {
		echo '<script>console.log("'.$msg.'")</script>';
	}
}

?>