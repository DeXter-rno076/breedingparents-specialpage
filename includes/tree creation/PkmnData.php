<?php
require_once __DIR__.'/../Pkmn.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';

class PkmnData extends Pkmn {
    private String $eggGroup1;
    private ?String $eggGroup2;

    /**
     * @var String gender of this pkmn; male | female | both | unknown
     */
    private String $gender;

    /**
     * @var String lowest evolution of this pkmn (e. g. Charmander for Charizard); 
     * if this pkmn is the lowest evo in its evo line this is set to the name of this pkmn (e. g. Abra for Abra) 
     */
    private String $lowestEvolution;

    private bool $unpairable;
    private bool $unbreedable;

    /**
     * @var Array level up, TMHM/TMTR and tutor learnsets of this pkmn
     * todo maybe tutor learnsets get their own list in the future
     */
    private Array $directLearnsets;
    private Array $breedingLearnsets;

    private Array $eventLearnsets;
    private Array $oldGenLearnsets;

    public function __construct (String $name) {
        $pkmnDataObj = Constants::$pkmnData->$name;
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
            'eventLearnsets', 'oldGenLearnsets'
        ];

        foreach ($mustHavePropertysList as $property) {
            if (!isset($pkmnDataObj->$property)) {
                Logger::elog('data object of '.$this->name
                    .' is missing property '.$property);
                throw new AttributeNotFoundException($this, $property);
            }
            $this->$property = $pkmnDataObj->$property;
        }

        $optionalPropertys = [];

        foreach ($optionalPropertys as $property) {
            if (isset($pkmnDataObj->$property)) {
                $this->$property = $pkmnDataObj->$property;
                continue;
            }
            $this->$property = null;
        }
    }

	/**
	 * checks Level, TMTR and Tutor learnsets
	 */
    public function canLearnDirectly (): bool {
        return $this->checkLearnsetType($this->directLearnsets, 'directly');
    }

    public function canInherit (): bool {
        if ($this->unbreedable) {
            Logger::statusLog($this.' is unbreedable');
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

    private function checkLearnsetType (Array $learnsetList, string $learnsetType): bool {
        foreach ($learnsetList as $move) {
            if ($move === Constants::$targetMove) {
                Logger::statusLog('found target move in '.$learnsetType.' learnset');
                return true;
            }
        }
        return false;
    }

    public function getEggGroup1 (): string {
        return $this->eggGroup1;
    }

    public function getEggGroup2 (): ?string {
        return $this->eggGroup2;
    }

    public function getGender (): string {
        return $this->gender;
    }

    public function getLowestEvolution (): string {
        return $this->lowestEvolution;
    }

    public function getUnpairable (): bool {
        return $this->unpairable;
    }

    public function hasSecondEggGroup (): bool {
        return $this->eggGroup2 !== null;
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