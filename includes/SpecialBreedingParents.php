<?php
//todo find a solution for output of validation methods
//todo choose either tabs or spaces
//todo look through all MediaWiki API methods and handle errors

require_once 'tree creation/BreedingTreeNode.php';
require_once 'svg creation/FrontendPkmn.php';
require_once 'svg creation/SVGTag.php';
require_once 'Constants.php';
require_once 'Logger.php';

class SpecialBreedingParents extends SpecialPage {
	public function __construct () {
		parent::__construct('Zuchteltern');
	}

	public function execute ($args) {
		$this->setHeaders();//seems like a must have
		$this->getOutput()->setPageTitle('Spezial:Zuchteltern');
		$this->addForms();
	}

	public function reactToInputData ($data, $form) {
		$this->initConstants($data);

        try {
		    $this->getData();
        } catch (Exception $e) {
            Constants::out('Oh nein, das hätte nicht passieren sollen :\'(\n'
                .'Fehler beim Ziehen der Daten: '.$e.'\n'
                .'Bitte melde das auf unserem Discordserver'
                .' oder in der '.Constants::auskunftLink.'.'
            );
            Logger::flush();
            return Status::newFailed('couldn\'t load data');
        }

        $targetPkmn = Constants::$targetPkmn;
        if (!isset(Constants::$pkmnData->$targetPkmn)) {
            Constants::out('unknown pkmn '.$targetPkmn);
            Logger::flush();
            return Status::newGood('unknown pkmn');
        }

        $breedingTreeRoot = null;
        try {
            $breedingTreeRoot = $this->createBreedingTree();
        } catch (AttributeNotFoundException $e) {
            Constants::out('Oh nein. Da ist uns ein Fehler passiert :( Bitte melde mit welchen Eingaben das hier passiert ist.');
            Logger::elog('couldn\'t create breeding tree, error: '.$e);
            return Status::newFatal($e->__toString());
        }
        if (is_null($breedingTreeRoot)) {
            //todo check whether move has a typo or generally if it's a move
            Constants::out(Constants::$targetPkmn.' can\'t learn '.Constants::$targetMove);
            Logger::flush();
            return Status::newGood('');
        }

        $frontendRoot = $this->createFrontendRoot($breedingTreeRoot);

        $this->createSVGStructure($frontendRoot);

        Logger::flush();
		return Status::newGood('all ok');
	}

    private function initConstants ($formData) {
        Constants::$targetGen = $formData['genInput'];
		Constants::$targetMove = $formData['moveInput'];
		Constants::$targetPkmn = $formData['pkmnInput'];
		if (isset($formData['displayDebuglogs'])) {
			Constants::$displayDebuglogs = $formData['displayDebuglogs'];
		}
		if (isset($formData['displayStatuslogs'])) {
			Constants::$displayStatuslogs = $formData['displayStatuslogs'];
		}

		Constants::$out = $this->getOutput();
    }

    private function createBreedingTree (): ?BreedingTreeNode {
        $timeStart = hrtime(true);

        $breedingTreeRoot = new BreedingTreeNode(Constants::$targetPkmn, true);
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
        Constants::$out->addModules('breedingParentsModules');
        Constants::plainOut(
            '<div id="breedingParentsSVGContainer" style="overflow: hidden;">'
            .$svgRoot->toHTMLString(100).'</div>');
        //adding button that resets the svg to the starting position
		Constants::$out->addHTML('<input type="button" id="breedingParentsSVGResetButton"'.
        ' value="Position zurücksetzen" />');

        $timeEnd = hrtime(true);
        $timeDiff = ($timeEnd - $timeStart) / 1_000_000_000;

        Logger::debugOut('svg creation needed: '.$timeDiff.'s');
    }

	private function addForms () {
		require_once 'formDescriptor.php';

        //todo put this in a separate method
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
            'inline', $formDescriptionArray, $this->getContext());
		$form->setMethod('get');
		$form->setSubmitCallback([$this, 'reactToInputData']);
		$form->setSubmitText('do it!');//todo text not final
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

		$blacklistPageName = 'MediaWiki:Zuchteltern/Gen'.$gen
            .'/pkmn-blacklist.json';
		Constants::$unbreedable = $this->getWikiPageContent(
            $blacklistPageName);
		
		$eggGroupPageName = 'MediaWiki:Zuchteltern/Gen'.$gen
            .'/egg-groups.json';
		Constants::$eggGroups = $this->getWikiPageContent($eggGroupPageName);
	}

	private function getPkmnData (String $gen) : StdClass {
		$pkmnDataArr = [];
		$pageData = null;
		$pageIndex = 1;

		do {
			$pkmnDataPageName = 'MediaWiki:Zuchteltern/Gen'.$gen
                .'/pkmn-data'.$pageIndex.'.json';
			$pageData = $this->getWikiPageContent($pkmnDataPageName);

			$pageDataArray = (array) $pageData;
			$pkmnDataArr = array_merge($pkmnDataArr, $pageDataArray);
			
			$pageIndex++;
		} while (isset($pageData->continue));

		$pkmnDataObj = (object) $pkmnDataArr;

		return $pkmnDataObj;
	}
	
	//original code written by Buo (thanks ^^)
	/*returns JSON objects and arrays
    -> needs 2 return types which needs php 8*/
	private function getWikiPageContent (String $name) {
		$title = Title::newFromText($name);
		$rev = Revision::newFromTitle($title);

		if (is_null($rev)) {
			throw new Exception('wiki page '.$name.' not found');
		}

		$data = $rev->getContent()->getNativeData();

		return json_decode($data);;
	}
}