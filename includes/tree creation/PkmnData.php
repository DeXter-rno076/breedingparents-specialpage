<?php
require_once __DIR__.'/../Pkmn.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';

class PkmnData extends Pkmn {
    private String $eggGroup1;
    private ?String $eggGroup2;
    private String $gender;
    private String $lowestEvolution = '';
    private bool $unpairable;
    private bool $unbreedable;

    private ?Array $directLearnsets;
    private ?Array $breedingLearnsets;
    private ?Array $eventLearnsets;

    public function __construct (String $name) {
        //todo check that all getter calls handle null values
        Logger::statusLog('creating PkmnData instance for '.$name);
        $pkmnDataObj = Constants::$pkmnData->$name;
        parent::__construct($name, $pkmnDataObj->id);
        $this->copyPropertys($pkmnDataObj);
    }

    /**
     * @throws AttributeNotFoundException
     */
    private function copyPropertys (StdClass $pkmnDataObj) {
        //logs commented out because they would make up large portions of the status log
        //Logger::statusLog('copying properties from JSON data to instance');
        $mustHavePropertysList = [
            'name', 'id', 'eggGroup1', 'gender', /* 'lowestEvolution', */
            'unpairable', 'unbreedable'
        ];

        foreach ($mustHavePropertysList as $property) {
            if (!isset($pkmnDataObj->$property)) {
                Logger::elog('data object of '.$this->name
                    .' is missing property '.$property);
                throw new AttributeNotFoundException($this, $property);
            }
            //Logger::statusLog('copying property '.$property);
            $this->$property = $pkmnDataObj->$property;
        }

        $optionalPropertys = [
            'eggGroup2', 'directLearnsets',
            'breedingLearnsets', 'eventLearnsets'
        ];

        foreach ($optionalPropertys as $property) {
            if (isset($pkmnDataObj->$property)) {
                //Logger::statusLog('property '.$property.' is set => copying');
                $this->$property = $pkmnDataObj->$property;
                continue;
            }
            //Logger::statusLog('property '.$property.' is not set => setting to null');
            $this->$property = null;
        }
    }

    public function getEggGroup1 (): string {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .', returning '.$this->eggGroup1);
        return $this->eggGroup1;
    }
    public function getEggGroup2 (): ?string {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .', returning '.$this->eggGroup2);
        return $this->eggGroup2;
    }
    public function getGender (): string {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .', returning '.$this->gender);
        return $this->gender;
    }
    public function getLowestEvolution (): string {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .', returning '.$this->lowestEvolution);
        return $this->lowestEvolution;
    }
    public function getUnpairable (): bool {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .', returning '.$this->unpairable);
        return $this->unpairable;
    }
    public function getUnbreedable (): bool {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this
            .', returning '.$this->unbreedable);
        return $this->unbreedable;
    }
    public function getDirectLearnsets (): ?Array {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this.', learnsets are '
            .(is_null($this->directLearnsets) ? '' : 'not').' null');
        return $this->directLearnsets;
    }
    public function getBreedingLearnsets (): ?Array {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this.', learnsets are '
            .(is_null($this->breedingLearnsets) ? '' : 'not').' null');
        return $this->breedingLearnsets;
    }
    public function getEventLearnsets (): ?Array {
        Logger::statusLog('calling '.__FUNCTION__.' on '.$this.', learnsets are '
            .(is_null($this->eventLearnsets) ? '' : 'not').' null');
        return $this->eventLearnsets;
    }

    public function getLogInfo(): string {
        return 'PkmnData:'.$this->name.';;';
    }

    public function __toString (): string {
        return $this->getLogInfo();
    }
}