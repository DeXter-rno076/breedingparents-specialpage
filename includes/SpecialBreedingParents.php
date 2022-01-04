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
		parent::__construct('BreedingParents');
	}

	public function execute ($args) {
        Constants::$specialPage = $this;
        Constants::$out = $this->getOutput();

		$this->setHeaders();//seems like a must have
		$this->getOutput()->setPageTitle($this->msg('breedingparents-title'));
		$this->addForms();
	}

	public function reactToInputData ($data, $form) {
		$this->initConstants($data);

        try {
		    $this->getData();
        } catch (Exception $e) {
            Constants::error($e);
            Logger::flush();
            return Status::newFatal('couldn\'t load data');
        }

        $targetPkmn = Constants::$targetPkmn;
        if (!isset(Constants::$pkmnData->$targetPkmn)) {
            Constants::out($this->msg('breedingparents-unknown-pkmn').Constants::$targetPkmn.'.');
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
            Constants::out(Constants::$targetPkmn.$this->msg('breedingparents-cant-learn'));
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
        Constants::directOut(
            '<div id="breedingParentsSVGContainer" style="overflow: hidden;">'
            .$svgRoot->toHTMLString(Constants::SVG_OFFSET).'</div>');

        $timeEnd = hrtime(true);
        $timeDiff = ($timeEnd - $timeStart) / 1_000_000_000;

        Logger::debugOut('svg creation needed: '.$timeDiff.'s');
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
		$form->setSubmitText($this->msg('breedingparents-submit-text')->__toString());
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
			Constants::outputOnce($this->msg('breedingparents-invalid-pkmn'));
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
            Constants::outputOnce($this->msg('breedingparents-invalid-move'));
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
            Constants::outputOnce($this->msg('breedingparents-invalid-gen'));
            return 'invalid gen';
		}

		return true;
	}

	private function getData () {
		$gen = Constants::$targetGen;
		Constants::$pkmnData = $this->getPkmnData($gen);

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