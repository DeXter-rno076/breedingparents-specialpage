<?php
require_once 'exceptions/AttributeNotFoundException.php';
require_once 'output_messages/InfoMessage.php';
require_once 'output_messages/AlertMessage.php';
require_once 'output_messages/ErrorMessage.php';
require_once 'output_messages/OutputMessage.php';
require_once 'tree_creation/BreedingTreeNode.php';
require_once 'svg_creation/FrontendPkmn.php';
require_once 'svg_creation/SVGTag.php';

require_once 'Constants.php';
require_once 'Logger.php';
require_once 'HTMLElement.php';

class SpecialBreedingChains extends SpecialPage {
	public function __construct () {
		parent::__construct('BreedingChains');
	}

	public function execute ($args) {
		Constants::$centralSpecialPageInstance = $this;
		Constants::$centralOutputPageInstance = $this->getOutput();

		Constants::$centralOutputPageInstance->setPageTitle(Constants::i18nMsg('breedingchains-title'));
		$this->setHeaders();//seems like a must have
		$this->addCSSandJS();
		$this->addForms();
	}

	public function submitCallback ($data, $form) {
		$successCode = '';
		try {
			$successCode = $this->reactToInput($data, $form);
		} catch (Exception $e) {
			$eMsg = new ErrorMessage($e);
			$eMsg->output();
			Logger::flush();
			return Status::newFatal((string) $e);
		}
		Logger::flush();
		return Status::newGood($successCode);
	}

	public function reactToInput ($data, $form): string {
		$this->initConstants($data);

		$findBetterName = $this->catchEasterEggs();
		if ($this->specialPageProcessIsFinished($findBetterName)) {
			return $findBetterName;
		}

		$this->getDataFromExternalWikipages();

		$findBetterName = $this->catchUnknownPkmnName();
		if ($this->specialPageProcessIsFinished($findBetterName)) {
			return $findBetterName;
		}

		$breedingTreeRoot = $this->createBreedingTree();

		$findBetterName = $this->catchNonStandardBreedingTreeStates($breedingTreeRoot);
		if ($this->specialPageProcessIsFinished($findBetterName)) {
			return $findBetterName;
		}

		$frontendRoot = $this->createFrontendRoot($breedingTreeRoot);

		$svgMapDiv = $this->createSVGMapDiv();
		$svgStructure = $this->createSVGStructure($frontendRoot);
		$this->addVisualStructuresToOutput($svgMapDiv, $svgStructure);

		return 'all ok';
	}

	private function initConstants ($formData) {
		Constants::$targetGenNumber = $formData['genInput'];
		Constants::$targetMoveName = $formData['moveInput'];
		Constants::$targetPkmnName = $formData['pkmnInput'];
		if (isset($formData['displayDebuglogs'])) {
			Constants::$displayDebuglogs = $formData['displayDebuglogs'];
		}
		if (isset($formData['displayStatuslogs'])) {
			Constants::$displayStatuslogs = $formData['displayStatuslogs'];
		}
	}

	private function specialPageProcessIsFinished (string $code): bool {
		return $code !== '';
	}

	private function catchEasterEggs (): string {
		$programTerminationCode = 'easter egg';

		if (Constants::$targetPkmnName === 'Greenchu') {
			$messageText = Constants::i18nMsg('breedingchains-easteregg-greenchu', Constants::$targetMoveName);
			$infoMessage = new InfoMessage($messageText);
			$infoMessage->output();
			return $programTerminationCode;
		}

		if (Constants::$targetPkmnName === 'DeXter') {
			$messageText = Constants::i18nMsg('breedingchains-easteregg-dexter', Constants::$targetMoveName);
			$infoMessage = new InfoMessage($messageText);
			$infoMessage->output();
			return $programTerminationCode;
		}

		return '';
	}

	private function catchUnknownPkmnName (): string {
		$programTerminationCode = 'unknown pkmn name';
		$targetPkmn = Constants::$targetPkmnName;
		if (!isset(Constants::$externalPkmnJSON->$targetPkmn)) {
			$messageText = Constants::i18nMsg('breedingchains-unknown-pkmn', Constants::$targetPkmnName);
			$alertMessage = new AlertMessage($messageText);
			$alertMessage->output();
			return $programTerminationCode;
		}
		return '';
	}

	private function catchNonStandardBreedingTreeStates (?BreedingTreeNode $breedingTreeRoot): string {
		if (is_null($breedingTreeRoot)) {
			return $this->catchEmptyBreedingTree();
		} else if (!$breedingTreeRoot->hasSuccessors()) {
			return $this->catchBreedingTreeRootLearnsDirectly($breedingTreeRoot);
		}
		return '';
	}

	private function catchEmptyBreedingTree (): string {
		//todo check whether move has a typo or generally if it's a move
		$infoMessage = new InfoMessage(Constants::i18nMsg('breedingchains-cant-learn', Constants::$targetPkmnName, Constants::$targetMoveName));
		$infoMessage->output();
		return 'cant learn';
	}

	private function catchBreedingTreeRootLearnsDirectly (BreedingTreeNode $breedingTreeRoot): string {
		//todo if a lowest evo can inherit the move but no suiting parents are found, this wouldnt be handled
		$infoMessage = null;
		if ($breedingTreeRoot->getLearnsByEvent()) {
			$infoMessage = new InfoMessage(Constants::i18nMsg(
				'breedingchains-can-learn-event', Constants::$targetPkmnName, Constants::$targetMoveName));
		} else if ($breedingTreeRoot->getLearnsByOldGen()) {
			$infoMessage = new InfoMessage(Constants::i18nMsg(
				'breedingchains-can-learn-oldgen', Constants::$targetPkmnName, Constants::$targetMoveName));
		} else {
			$infoMessage = new InfoMessage(Constants::i18nMsg(
				'breedingchains-can-learn-directly', Constants::$targetPkmnName,
				Constants::$targetMoveName));
		}
		$infoMessage->output();
		return 'can learn directly';
	}

	private function createBreedingTree (): ?BreedingTreeNode {
		Logger::statusLog('CREATING BREEDING TREE NODES');
		$timeStart = hrtime(true);

		$breedingTreeRoot = new BreedingTreeNode(Constants::$targetPkmnName, true);
		$breedingTreeRoot = $breedingTreeRoot->createBreedingTreeNode([]);

		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		Logger::outputDebugMessage('breeding tree creation needed: '.$timeDiff.'s');

		return $breedingTreeRoot;
	}

	private function createFrontendRoot (BreedingTreeNode $breedingTreeRoot): FrontendPkmn {
		Logger::statusLog('CREATING FRONTENDPKMN INSTANCES');
		$timeStart = hrtime(true);

		$frontendRoot = new FrontendPkmn($breedingTreeRoot);
		$frontendRoot->setTreeIconsAndCoordinates();
	
		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		Logger::outputDebugMessage('creating frontend pkmn tree needed: '.$timeDiff.'s');

		return $frontendRoot;
	}

	private function createSVGStructure (FrontendPkmn $frontendRoot): HTMLElement {
		Logger::statusLog('CREATING SVG STRUCTURE');
		$timeStart = hrtime(true);

		$svgRoot = new SVGTag($frontendRoot, Constants::UNUSED_GROUP_ID);
		$svgStructureInHTML = $svgRoot->toHTML();

		$timeEnd = hrtime(true);
		$timeDiff = ($timeEnd - $timeStart) / 1000000000;
		Logger::outputDebugMessage('svg creation needed: '.$timeDiff.'s');

		return $svgStructureInHTML;
	}

	private function createSVGMapDiv (): HTMLElement {
		$mapDiv = new HTMLElement('div', [
			'id' => 'breedingChainsSVGMap',
		]);
		return $mapDiv;
	}

	private function addVisualStructuresToOutput (HTMLElement $svgMapDiv, HTMLElement $svgStructure) {
		$this->addMarkerExplanations();
		$svgMapDiv->addToOutput();
		$svgStructure->addToOutput();
	}

	private function addMarkerExplanations () {
		require_once 'markerExamples.php';
		$markerExamplesTable->addToOutput();
	}

	private function addCSSandJS () {
		Constants::$centralOutputPageInstance->addModules('breedingChainsModules');
	}

	private function addForms () {
		$formDescriptionArray = $this->getUserGroupSpecificFormDescription();

		$form = HTMLForm::factory(
			'ooui', $formDescriptionArray, $this->getContext());
		$form->setMethod('get');
		$form->setSubmitCallback([$this, 'submitCallback']);
		$form->setSubmitText(Constants::i18nMsg('breedingchains-submit-text'));
		$form->prepareForm();

		$form->displayForm('');
		$form->trySubmit();
	}

	private function getUserGroupSpecificFormDescription () {
		require_once 'formDescriptor.php';

		$user = $this->getUser();
		$userGroups = $user->getGroupMemberships();

		//todo put the group names in some kind of config file
		if (isset($userGroups['voting'])) {
			$formDescriptor = array_merge($formDescriptor,
				$debuglogsCheckBox);
		}

		if (isset($userGroups['leader'])) {
			$formDescriptor = array_merge($formDescriptor,
				$statuslogsCheckBox);
		}

		return $formDescriptor;
	}

	//has to be public
	public function validatePkmnInput ($value, $allData) {
		if ($value === '' || $value === null) {
			return true;
		}

		//these are all characters that are used in pkmn names
		$regex = '/[^a-zA-Zßäéü\-♂♀2:]/';
		if (preg_match($regex, $value)) {
			$alertMessage = new AlertMessage(Constants::i18nMsg('breedingchains-invalid-pkmn'));
			$alertMessage->outputOnce();
			return 'invalid pkmn';
		}

		return true;
	}

	//has to be public
	public function validateMoveInput ($value, $allData) {
		if ($value === '' || $value === null) {
			return true;
		}

		//these are all characters that are used in move names
		$regex = '/[^a-zA-ZÜßäöü\- 2]/';
		if (preg_match($regex, $value)) {
			$alertMessage = new AlertMessage(Constants::i18nMsg('breedingchains-invalid-move'));
			$alertMessage->outputOnce();
			return 'invalid move';
		}

		return true;
	}

	//has to be public
	public function validateGenInput ($value, $allData) {
		if ($value === '' || $value === null) {
			return true;
		}

		if (!is_numeric($value)) {
			$alertMessage = new AlertMessage(Constants::i18nMsg('breedingchains-invalid-gen'));
			$alertMessage->outputOnce();
			return 'invalid gen';
		}

		return true;
	}

	private function getDataFromExternalWikipages () {
		Constants::$externalPkmnJSON = $this->getExternalJSONPkmnData();

		$eggGroupPageName = 'MediaWiki:BreedingChains/Gen'
			.Constants::$targetGenNumber.'/egg-groups.json';
		Constants::$externalEggGroupsJSON = $this->getWikiPageContent($eggGroupPageName);
	}

	private function getExternalJSONPkmnData () : StdClass {
		$pkmnDataArr = [];
		$pageData = null;
		$pageIndex = 1;

		do {
			$pkmnDataPageName = 'MediaWiki:BreedingChains/Gen'
				.Constants::$targetGenNumber.'/pkmn-data'.$pageIndex.'.json';
			$pageData = $this->getWikiPageContent($pkmnDataPageName);

			$pageDataArray = (array) $pageData;
			$pkmnDataArr = array_merge($pkmnDataArr, $pageDataArray);

			$pageIndex++;
		} while (isset($pageData->continue));

		$pkmnDataObj = (object) $pkmnDataArr;

		return $pkmnDataObj;
	}

	/*original code written by Buo and code
	without deprecated parts written by Robbi (thanks ^^)

	returns JSON objects and arrays
	-> needs 2 return types which needs php 8*/
	public function getWikiPageContent (string $title) {
		$page = WikiPage::factory(Title::newFromText($title));

		if(!$page->exists()) {
			throw new Exception('wiki page '.$title.' not found');
		}

		$pageData = ContentHandler::getContentText($page->getContent());

		return json_decode($pageData);
	}
}