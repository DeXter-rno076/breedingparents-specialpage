<?php
require_once 'output_messages/AlertMessage.php';
require_once 'output_messages/ErrorMessage.php';
require_once 'output_messages/InfoMessage.php';
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
		$success = $this->initConstants($data);
		if (!$success) {
			return 'invalid input';
		}

		$preDataloadingCheckpoint = new PreDataLoadingCheckPoint();
		return $preDataloadingCheckpoint->passOn();
	}

	//todo split up / outsource
	private function initConstants ($formData): bool {
		//todo i dont like these magic strings
		Constants::$GAME_LIST = json_decode(file_get_contents(__DIR__.'/../manual_data/gamesToSk.json'));
		Constants::$GAMES_TO_GEN = json_decode(file_get_contents(__DIR__.'/../manual_data/gamesToGen.json'));
		Constants::$MOVE_NAMES = json_decode(file_get_contents(__DIR__.'/../manual_data/moveNames.json'));
		Constants::$MOVE_NAME_TO_NEW_MOVE_NAME = json_decode(file_get_contents(__DIR__.'/../manual_data/renamedMoves.json'));

		$gameInput = $formData['gameInput'];
		if (!isset(Constants::$GAME_LIST->$gameInput)) {
			$infoMsg = new InfoMessage(Constants::i18nMsg('breedingchains-unknown-game', $gameInput));
			$infoMsg->output();
			return false;
		}
		$targetGameString = Constants::$GAME_LIST->$gameInput;
		Constants::$targetGameString = $targetGameString;
		Constants::$targetGenNumber = Constants::$GAMES_TO_GEN->$targetGameString;

		Constants::$targetMoveNameOriginalInput = $formData['moveInput'];
		Constants::$targetMoveName = $this->buildInternalMoveName($formData['moveInput']);
		Constants::$targetPkmnNameOriginalInput = trim($formData['pkmnInput']);
		Constants::$targetPkmnName = mb_strtolower(Constants::$targetPkmnNameOriginalInput);

		if (isset($formData['displayDebuglogs'])) {
			Constants::$displayDebuglogs = $formData['displayDebuglogs'];
		}
		if (isset($formData['displayStatuslogs'])) {
			Constants::$displayStatuslogs = $formData['displayStatuslogs'];
		}

        if (isset($formData['createDetailedSuccessorFilterLogs'])) {
            Constants::$createDetailedSuccessorFilterLogs = $formData['createDetailedSuccessorFilterLogs'];
        }

		Constants::logUserinputConstants();

		return true;
	}

	private function buildInternalMoveName (string $moveInput): string {
		$internalMoveName = trim($moveInput);
		if (isset(Constants::$MOVE_NAME_TO_NEW_MOVE_NAME->$internalMoveName)) {
			$internalMoveName = Constants::$MOVE_NAME_TO_NEW_MOVE_NAME->$internalMoveName;
		}
		return mb_strtolower($internalMoveName);
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

		//todo put the group name in some kind of config file
		if (isset($userGroups['voting'])) {
			$formDescriptor = array_merge($formDescriptor,
				$debuglogsCheckBox);

            $formDescriptor = array_merge($formDescriptor,
				$statuslogsCheckBox);

            $formDescriptor = array_merge($formDescriptor,
                $detailedSuccessorFilterLogsCheckBox);
		}

		return $formDescriptor;
	}

	private function addSuggestions () {
		$moveSuggestions = $this->loadSplitExternalJSON('MediaWiki:BreedingChains/move suggestions ##INDEX##.json');
		$moveSuggestionsAsText = json_encode($moveSuggestions);
		$moveSuggestionsAsTextWithoutWhiteSpace = str_replace([' ', '\n'], '', $moveSuggestionsAsText);

		$pkmnSuggestions = array_keys((array) $moveSuggestions);
		$pkmnSuggestionsAsText = json_encode($pkmnSuggestions);

        $gameToSk = json_decode(file_get_contents(__DIR__.'/../manual_data/gamesToSk.json'));
        Constants::$centralOutputPageInstance->addJsConfigVars([
            'breedingchains-moveSuggestions' => $moveSuggestions,
            'breedingchains-pkmnSuggestions' => $pkmnSuggestions,
            'breedingchains-gameToSk' => $gameToSk
        ]);
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