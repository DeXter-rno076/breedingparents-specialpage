<?php
require_once __DIR__.'/../Pkmn.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';

class PkmnData extends Pkmn {
    private String $eggGroup1;
    private String $eggGroup2;
    private String $gender;
    private String $lowestEvolution;

    private bool $unpairable;
    private bool $unbreedable;

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

    public function getEggGroup1 (): string {
        return $this->eggGroup1;
    }
    public function getEggGroup2 (): string {
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
    public function getUnbreedable (): bool {
        return $this->unbreedable;
    }
    public function getDirectLearnsets (): Array {
        return $this->directLearnsets;
    }
    public function getBreedingLearnsets (): Array {
        return $this->breedingLearnsets;
    }
    public function getEventLearnsets (): Array {
        return $this->eventLearnsets;
    }

    public function hasSecondEggGroup (): bool {
        return $this->eggGroup2 !== '';
    }

    public function getOldGenLearnsets (): Array {
        return $this->oldGenLearnsets;
    }

    public function getLogInfo(): string {
        return 'PkmnData:'.$this->name.';;';
    }

    public function __toString (): string {
        return $this->getLogInfo();
    }
}