<?php
//todo find a solution for output of validation methods

require 'BackendHandler.php';
require 'FrontendHandler.php';

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
		
		$backendHandler = new BackendHandler(
			$this->pkmnData,
			$this->eggGroups,
			$this->unbreedable,
			$targetPkmn,
			$targetMove,
			$this->getOutput(),
		);

		$breedingTree = $backendHandler->createBreedingTree();

		if (is_null($breedingTree)) {
			return Status::newFatal('breeding tree empty');
		}

		/* $fileRepo = new FileRepo([
			'descBaseUrl' => 'https://www.pokewiki.de/Datei:',
			'scriptDirUrl' => 'https://www.pokewiki.de/',
			'articleUrl' => 'https://www.pokewiki.de/$1'

		]);
		$pkmnicon = new LocalFile('Datei:Pokémon-Icon 150.png', $fileRepo);
		$iconurl = $pkmnicon->getUrl();
		$this->debugOutput($iconurl); */

		/* $filetest = fopen('testOutput.json', 'r');
		fclose($filetest); */

		/* $test_fileAccessTime = fileatime('testOutput.json');
		$this->debugOutput(json_encode($test_fileAccessTime)); */

		$frontendHandler = new FrontendHandler($breedingTree, $this->pkmnData);
		$frontendHandler->addSVG($this->getOutput());

		return Status::newGood();
	}

	private function addForms () {
		require 'formDescriptor.php';
	
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
	
		$regex = '/[^a-zA-Zßäéü\-♂♀2:]/';//these are all characters that are used in pkmn names
		if (preg_match($regex, $value)) {
			$this->debugOutput('pkmn name is evil >:(');
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
			$this->debugOutput('move name is evil >:(');
			return 'Invalid character in the move name';
		}
		
		return true;
	}

	//has to be public
	public function validateGen ($value, $allData) {
		if (!is_numeric($value)) {
			$this->debugOutput('gen is evil >:(');
			return 'Invalid gen input';
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