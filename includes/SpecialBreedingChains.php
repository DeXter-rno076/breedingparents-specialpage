<?php
require_once 'tree creation/BreedingTreeNode.php';
require_once 'svg creation/FrontendPkmn.php';
require_once 'svg creation/SVGTag.php';
require_once 'Constants.php';
require_once 'Logger.php';
require_once 'HTMLElement.php';
require_once __DIR__.'/exceptions/AttributeNotFoundException.php';

class SpecialBreedingChains extends SpecialPage {
    public function __construct () {
        parent::__construct('BreedingChains');
    }

    public function execute ($args) {
        Constants::$centralSpecialPageInstance = $this;
        Constants::$centralOutputPageInstance = $this->getOutput();

        $this->setHeaders();//seems like a must have
        $this->getOutput()->setPageTitle($this->msg('breedingchains-title'));
		$this->addCSSandJS();
        $this->addForms();
    }

	//todo split up
    public function reactToInputData ($data, $form) {
        $this->initConstants($data);

		if (Constants::$targetPkmnName === 'Greenchu') {
			Constants::outputInfoMessage('Das Phänomen Greenchu ist noch weitgehend unerforscht. Dementsprechend wissen wir nicht, ob es $1 überhaupt erlernen kann. Selbst in der Entstehungsgeschichte bestehen noch große Lücken. Falls du dort etwas weißt, teile es uns bitte mit :D');
			Logger::flush();
			return Status::newGood('easter egg');
		}
		if (Constants::$targetPkmnName === 'DeXter') {
			Constants::outputInfoMessage('... Ob ich $1 erlernen kann? Ich bin vielleicht angehender professioneller Druckerreparierer, Teilzeit-Mod und Discord-Hobbypsychologe, aber ich bin (leider oder zum Glück) kein Pokémon!');
			Logger::flush();
			return Status::newGood('easter egg');
		}

        try {
            $this->getData();
        } catch (Exception $e) {
            Constants::error($e);
            Logger::flush();
            return Status::newFatal('couldn\'t load data');
        }

        $targetPkmn = Constants::$targetPkmnName;
        if (!isset(Constants::$externalPkmnJSON->$targetPkmn)) {
            Constants::outputAlertMessage($this->msg('breedingchains-unknown-pkmn', Constants::$targetPkmnName));
            Logger::flush();
            return Status::newGood('unknown pkmn');
        }

        $breedingTreeRoot = null;
        try {
            $breedingTreeRoot = $this->createBreedingTree();
        } catch (AttributeNotFoundException $e) {
            Constants::error($e);
            Logger::elog('couldn\'t create breeding tree, error: '.$e);
            return Status::newFatal($e->__toString());
        }
        if (is_null($breedingTreeRoot)) {
            //todo check whether move has a typo or generally if it's a move
            Constants::outputInfoMessage($this->msg('breedingchains-cant-learn', Constants::$targetPkmnName, Constants::$targetMoveName));
            Logger::flush();
            return Status::newGood('cant learn');
        } else if (!$breedingTreeRoot->hasSuccessors()) {
			if ($breedingTreeRoot->getLearnsByEvent()) {
				Constants::outputInfoMessage($this->msg(
					'breedingchains-can-learn-event', Constants::$targetPkmnName));
			} else if ($breedingTreeRoot->getLearnsByOldGen()) {
				Constants::outputInfoMessage($this->msg(
					'breedingchains-can-learn-oldgen', Constants::$targetPkmnName));
			} else {
				Constants::outputInfoMessage($this->msg(
					'breedingchains-can-learn-directly', Constants::$targetPkmnName,
					Constants::$targetMoveName));
				
			}
			Logger::flush();
			return Status::newGood('can learn directly');
        }

        $frontendRoot = $this->createFrontendRoot($breedingTreeRoot);

        $this->addMarkerExplanations();

        $this->createSVGStructure($frontendRoot);

        Logger::flush();
        return Status::newGood('all ok');
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

    private function createBreedingTree (): ?BreedingTreeNode {
        $timeStart = hrtime(true);

        $breedingTreeRoot = new BreedingTreeNode(Constants::$targetPkmnName, true);
        Logger::statusLog('CREATING BREEDING TREE NODES');
        $breedingTreeRoot = $breedingTreeRoot->createBreedingTreeNode([]);

        $timeEnd = hrtime(true);
        $timeDiff = ($timeEnd - $timeStart) / 1_000_000_000;

        Logger::debugOut('breeding tree creation needed: '.$timeDiff.'s');

        return $breedingTreeRoot;
    }

    private function createFrontendRoot (BreedingTreeNode $breedingTreeRoot): FrontendPkmn {
        Logger::statusLog('CREATING FRONTENDPKMN INSTANCES');
        $timeStart = hrtime(true);
        $frontendRoot = new FrontendPkmn($breedingTreeRoot);
        $frontendRoot->setTreeIconsAndCoordinates();
        $timeEnd = hrtime(true);
        $timeDiff = ($timeEnd - $timeStart) / 1_000_000_000;
        Logger::debugOut('creating frontend pkmn tree needed: '.$timeDiff.'s');

        return $frontendRoot;
    }

    private function createSVGStructure (FrontendPkmn $frontendRoot) {
        Logger::statusLog('CREATING SVG STRUCTURE');
        $timeStart = hrtime(true);

        $svgRoot = new SVGTag($frontendRoot);

        $mapDiv = new HTMLElement('div', [
            'id' => 'breedingChainsSVGMap',
        ]);
        $mapDiv->addToOutput();
		$svgRoot->toHTML()->addToOutput();

        $timeEnd = hrtime(true);
        $timeDiff = ($timeEnd - $timeStart) / 1_000_000_000;

        Logger::debugOut('svg creation needed: '.$timeDiff.'s');
    }

	private function addCSSandJS () {
		Constants::$centralOutputPageInstance->addModules('breedingChainsModules');
	}

    private function addMarkerExplanations () {
        require_once 'markerExamples.php';
        $markerExamplesTable->addToOutput();
    }

    private function addForms () {
        require_once 'formDescriptor.php';

        $formDescriptionArray = $formDescriptor;
        $user = $this->getUser();
        $userGroups = $user->getGroupMemberships();
        //todo put the group names in some kind of config file
        if (isset($userGroups['voting'])) {
            $formDescriptionArray = array_merge($formDescriptionArray,
                $debuglogsCheckBox);
        }
        if (isset($userGroups['trusted'])) {
            $formDescriptionArray = array_merge($formDescriptionArray,
                $statuslogsCheckBox);
        }

        $form = HTMLForm::factory(
            'ooui', $formDescriptionArray, $this->getContext());
        $form->setMethod('get');
        $form->setSubmitCallback([$this, 'reactToInputData']);
        $form->setSubmitText($this->msg('breedingchains-submit-text')->__toString());
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
            Constants::outputOnceAlertMessage($this->msg('breedingchains-invalid-pkmn'));
            return 'invalid pkmn';
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
            Constants::outputOnceAlertMessage($this->msg('breedingchains-invalid-move'));
            return 'invalid move';
        }

        return true;
    }

    //has to be public
    public function validateGen ($value, $allData) {
        if ($value === '' || $value === null) {
            return true;
        }

        if (!is_numeric($value)) {
            Constants::outputOnceAlertMessage($this->msg('breedingchains-invalid-gen'));
            return 'invalid gen';
        }

        return true;
    }

    private function getData () {
        $gen = Constants::$targetGenNumber;
        Constants::$externalPkmnJSON = $this->getPkmnData($gen);

        $eggGroupPageName = 'MediaWiki:'.$this->msg('breedingchains').'/Gen'.$gen
            .'/egg-groups.json';
        Constants::$externalEggGroupsJSON = $this->getWikiPageContent($eggGroupPageName);
    }

    private function getPkmnData (String $gen) : StdClass {
        $pkmnDataArr = [];
        $pageData = null;
        $pageIndex = 1;

        do {
            $pkmnDataPageName = 'MediaWiki:'.$this->msg('breedingchains').'/Gen'.$gen
                .'/pkmn-data'.$pageIndex.'.json';
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