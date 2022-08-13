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
        $this->addLoadingBar();
        $this->addFormContainer();
        $this->addCSSandJS();
        $this->startExecPath();
    }

    private function addLoadingBar () {
        Constants::$centralOutputPageInstance->enableOOUI();

        $loadingBar = new OOUI\ProgressBarWidget([
            'id' => 'specialBreedingChainsLoadingBar',
            'progress' => false,
        ]);

        Constants::$centralOutputPageInstance->addHTML($loadingBar);
    }

    private function addFormContainer () {
        $formContainer = new HTMLElement('div', [
            'id' => 'specialBreedingChainsFormContainer'
        ], [
            new HTMLElement('noscript', [], [
                Constants::i18nMsg('breedingchains-noscript')
            ])
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
            'breedingchains-move-required' => Constants::i18nMsg('breedingchains-move-required'),
            'breedingchains-whatshappening' => Constants::i18nMsg('breedingchains-whatshappening'),
            'breedingchains-submit-text' => Constants::i18nMsg('breedingchains-submit-text'),
            'breedingchains-unknown-game' => Constants::i18nMsg('breedingchains-unknown-game'),
            'breedingchains-unknown-pkmn' => Constants::i18nMsg('breedingchains-unknown-pkmn'),
            'breedingchains-move-not-suggested' => Constants::i18nMsg('breedingchains-move-not-suggested'),
            'breedingchains-popup-header' => Constants::i18nMsg('breedingchains-popup-header'),
            'breedingchains-popup-learns-d' => ((Constants::$targetGenNumber < 8)
                ? Constants::i18nMsg('breedingchains-popup-learns-d-old') : Constants::i18nMsg('breedingchains-popup-learns-d-new')),
            'breedingchains-popup-learns-b' => Constants::i18nMsg('breedingchains-popup-learns-b'),
            'breedingchains-popup-learns-o' => ((Constants::$targetGenNumber < 8)
                ? Constants::i18nMsg('breedingchains-popup-learns-o-old') : Constants::i18nMsg('breedingchains-popup-learns-o-new')),
            'breedingchains-popup-learns-e' => Constants::i18nMsg('breedingchains-popup-learns-e'),
            'breedingchains-popup-error' => Constants::i18nMsg('breedingchains-popup-error')
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

        unset($pkmnDataArr['continue']);

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
            Logger::statusLog('success code: '.$successCode);
            Logger::flush();
        }

        return Status::newGood($successCode);
    }
}