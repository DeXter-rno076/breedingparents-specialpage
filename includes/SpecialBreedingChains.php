<?php
require_once 'output_messages/AlertMessage.php';
require_once 'output_messages/ErrorMessage.php';
require_once 'output_messages/InfoMessage.php';
require_once 'exec_path/FormValidationCheckpoint.php';

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
        $this->addFormContainer();
		$this->addCSSandJS();
		$this->startExecPath();
	}

    private function addFormContainer () {
        $formContainer = new HTMLElement('div', [
            'id' => 'specialBreedingChainsFormContainer'
        ]);
        $formContainer->addToOutput();
    }

    private function addCSSandJS () {
        $this->addJSConfigVars();
		Constants::$centralOutputPageInstance->addModules('breedingChainsModules');
	}

    private function addJSConfigVars () {
		$moveSuggestions = $this->loadSplitExternalJSON('MediaWiki:BreedingChains/move suggestions ##INDEX##.json');
		$pkmnSuggestions = array_keys((array) $moveSuggestions);
        $gameToSk = json_decode(file_get_contents(__DIR__.'/../manual_data/gamesToSk.json'));

        $user = $this->getUser();
        $userGroups = $user->getGroupMemberships();

        Constants::$centralOutputPageInstance->addJsConfigVars([
            'breedingchains-move-suggestions' => $moveSuggestions,
            'breedingchains-pkmn-suggestions' => $pkmnSuggestions,
            'breedingchains-game-to-sk' => $gameToSk,
            'breedingchains-display-debug-checkboxes' => isset($userGroups['voting']),
            'breedingchains-game-input-placeholder' => Constants::i18nMsg('breedingchains-game'),
            'breedingchains-pkmn-input-placeholder' => Constants::i18nMsg('breedingchains-pkmn'),
            'breedingchains-move-input-placeholder' => Constants::i18nMsg('breedingchains-move'),
            'breedingchains-game-required' => Constants::i18nMsg('breedingchains-game-required'),
            'breedingchains-pkmn-required' => Constants::i18nMsg('breedingchains-pkmn-required'),
            'breedingchains-move-required' => Constants::i18nMsg('breedingchains-move-required')
        ]);
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

	public function startExecPath () {
		$successCode = '';
        try {
            $formValidationCheckPoint = new FormValidationCheckpoint($_GET);
            $successCode = $formValidationCheckPoint->passOn();
        } catch (Exception $e) {
            $eMsg = new ErrorMessage($e);
            $eMsg->output();

            return Status::newFatal((string) $e);
        } finally {
            Logger::flush();
        }

        echo $successCode;

		return Status::newGood($successCode);
	}
}