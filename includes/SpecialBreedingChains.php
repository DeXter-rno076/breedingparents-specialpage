<?php
require_once 'exceptions/AttributeNotFoundException.php';
require_once 'output_messages/InfoMessage.php';
require_once 'output_messages/AlertMessage.php';
require_once 'output_messages/ErrorMessage.php';
require_once 'output_messages/OutputMessage.php';
require_once 'tree_creation/BreedingTreeNode.php';
require_once 'svg_creation/FrontendPkmn.php';
require_once 'svg_creation/SVGTag.php';
require_once 'exec_path/PreDataLoadingCheckpoint.php';

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
		if ($this->formIsEmpty($data)) {
			$successCode = 'empty form';
		} else {
			try {
				$successCode = $this->reactToInput($data, $form);
			} catch (Exception $e) {
				$eMsg = new ErrorMessage($e);
				$eMsg->output();
				Logger::flush();
				return Status::newFatal((string) $e);
			}
		}
		Logger::flush();
		return Status::newGood($successCode);
	}

	private function formIsEmpty ($data) {
		return is_null($data['pkmnInput']) || is_null($data['moveInput']) || is_null($data['gameInput']);
	}

	public function reactToInput ($data, $form): string {
		$this->initConstants($data);

		$preDataloadingCheckpoint = new PreDataLoadingCheckPoint();
		return $preDataloadingCheckpoint->passOn();
	}

	private function initConstants ($formData) {
		Constants::$targetGameString = Constants::GAME_LIST[$formData['gameInput']];
		Constants::$targetGenNumber = Constants::GAMES_TO_GEN[Constants::$targetGameString];

		Constants::$targetMoveNameNormalCasing = trim($formData['moveInput']); 
		Constants::$targetMoveName = strtolower(Constants::$targetMoveNameNormalCasing);
		Constants::$targetPkmnNameNormalCasing = trim($formData['pkmnInput']);
		Constants::$targetPkmnName = strtolower(Constants::$targetPkmnNameNormalCasing);
		
		if (isset($formData['displayDebuglogs'])) {
			Constants::$displayDebuglogs = $formData['displayDebuglogs'];
		}
		if (isset($formData['displayStatuslogs'])) {
			Constants::$displayStatuslogs = $formData['displayStatuslogs'];
		}

		Constants::logUserinputConstants();
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

		$this->addSuggestions();
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

	//TODO THIS IS UNCLEAN AF
	private function addSuggestions () {
		$moveSuggestions = $this->loadSplitExternalJSON('MediaWiki:BreedingChains/move suggestions ##INDEX##.json');
		$moveSuggestionsAsText = json_encode($moveSuggestions);
		$moveSuggestionsAsTextWithoutWhiteSpace = str_replace([' ', '\n'], '', $moveSuggestionsAsText);

		$gen2Commons = $this->loadSplitExternalJSON('MediaWiki:BreedingChains/Gen2/commons ##INDEX##.json');
		$pkmnSuggestions = array_keys((array) $gen2Commons);
		$pkmnSuggestionsAsText = json_encode($pkmnSuggestions);
		$suggestionsScriptTag = new HTMLElement('script', [], [
			'const MOVE_SUGGESTIONS = '.$moveSuggestionsAsTextWithoutWhiteSpace.';'
			.'const PKMN_SUGGESTIONS = '.$pkmnSuggestionsAsText.';'
		]);
		$suggestionsScriptTag->addToOutput();
	}

	//has to be public
	public function validatePkmnInput ($value, $allData) {
		if ($value === '' || $value === null) {
			return true;
		}

		//these are all characters that are used in pkmn names
		$regex = '/[^a-zA-Zßäéü\-♂♀2:\s]/';
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
		$regex = '/[^a-zA-ZÜßäöü\- 2\s]/';
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

	public function validateGameInput ($value, $allData) {
		//todo
		return true;
	}

	public function loadSplitExternalJSON (string $pageNameScheme): StdClass {
		$pkmnDataArr = [];
		$pageData = null;
		$pageIndex = 0;

		do {
			$pkmnDataPageName = str_replace('##INDEX##', $pageIndex, $pageNameScheme);
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