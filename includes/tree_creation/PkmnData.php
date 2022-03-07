<?php
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';
require_once __DIR__.'/../Pkmn.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../Logger.php';

require_once 'BreedingTreeNode.php';

class PkmnData extends Pkmn {
	private $eggGroup1;
	private $eggGroup2;

	// male | female | both | unknown
	private $gender;

	/**
	 * @var String lowest evolution of this pkmn (e. g. Charmander for Charizard); 
	 * if this pkmn is the lowest evo in its evo line this is set to the name of this pkmn (e. g. Abra for Abra) 
	 */
	private $lowestEvolution;
	private $evolutions;

	private $unpairable;
	private $unbreedable;

	/**
	 * @var Array level up, TMHM/TMTR and tutor learnsets of this pkmn
	 * todo maybe tutor learnsets get their own list in the future
	 */
	private $directLearnsets;
	private $breedingLearnsets;

	private $eventLearnsets;
	private $oldGenLearnsets;

	public function __construct (string $name) {
		$pkmnDataObj = Constants::$externalPkmnJSON->$name;

		parent::__construct($name, $pkmnDataObj->id);

		$this->copyPropertys($pkmnDataObj);
	}

	/**
	 * Copies values from the JSON obj of this pkmn to this instance.
	 * If a must have value is not set in the JSON obj, this throws an AttributeNotFoundException
	 * @param StdClass $pkmnDataObj obj from the external JSON data
	 * 
	 * @throws AttributeNotFoundException
	 */
	private function copyPropertys (StdClass $pkmnDataObj) {
		$mustHavePropertysList = [
			'eggGroup1', 'eggGroup2', 'gender', 'lowestEvolution',
			'unpairable', 'unbreedable', 'directLearnsets', 'breedingLearnsets',
			'eventLearnsets', 'oldGenLearnsets', 'evolutions'
		];

		foreach ($mustHavePropertysList as $property) {
			if (!isset($pkmnDataObj->$property)) {
				throw new AttributeNotFoundException($this, $property);
			}
			$this->$property = $pkmnDataObj->$property;
		}

		$optionalPropertys = [];

		foreach ($optionalPropertys as $property) {
			if (isset($pkmnDataObj->$property)) {
				$this->$property = $pkmnDataObj->$property;
			} else {
				$this->$property = null;
			}
		}
	}

	/**
	 * checks Level, TMTR and Tutor learnsets
	 */
	public function canLearnDirectly (): bool {
		return $this->checkLearnsetType($this->directLearnsets, 'directly');
	}

	private function checkLearnsetType (Array $learnsetList, string $learnsetType): bool {
		foreach ($learnsetList as $move) {
			if ($move === Constants::$targetMoveName) {
				Logger::statusLog('found target move in '.$learnsetType.' learnset');
				return true;
			}
		}
		return false;
	}

	public function canInherit (): bool {
		if ($this->unbreedable) {
			return false;
		}

		return $this->checkLearnsetType($this->breedingLearnsets, 'breeding');
	}

	public function canLearnByEvent (): bool {
		return $this->checkLearnsetType($this->eventLearnsets, 'event');
	}

	public function canLearnByOldGen (): bool {
		return $this->checkLearnsetType($this->oldGenLearnsets, 'old gen');
	}

	public function getEggGroup1 (): string {
		return $this->eggGroup1;
	}

	public function getEggGroup2 (): string {
		return $this->eggGroup2;
	}

	public function isFemaleOnly (): bool {
		return $this->gender === 'female';
	}

	public function isMaleOnly (): bool {
		return $this->gender === 'male';
	}

	public function hasNoGender (): bool {
		return $this->gender === 'unknown';
	}

	public function isLowestEvolution (): bool {
		return $this->lowestEvolution === $this->name;
	}

	public function getLowestEvolutionBreedingTreeInstance (bool $isRoot = false): BreedingTreeNode {
		return new BreedingTreeNode($this->lowestEvolution, $isRoot);
	}

	public function isUnpairable (): bool {
		return $this->unpairable;
	}

	public function hasSecondEggGroup (): bool {
		return $this->eggGroup2 !== '';
	}

	public function hasAsEvolution (string $pkmnName): bool {
		return in_array($pkmnName, $this->evolutions);
	}

	/**
	 * @return string PkmnData:<pkmn name>;;
	 * Is never used, because PkmnData instances are created all the time
	 * and would flood the status log.
	 */
	public function getLogInfo(): string {
		return 'PkmnData:'.$this->name.';;';
	}
}