<?php
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';
require_once __DIR__.'/../Pkmn.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../Logger.php';

require_once 'PkmnTreeNode.php';

class PkmnData extends Pkmn {
    private $id;
    private $game;
    private $exists;

    private $correctlyWrittenName;
    private $subpageLinkName;

    private $eggGroup1;
    private $eggGroup2;

    // male | female | both | unknown
    private $gender;

    /**
     * @var String lowest evolution of this pkmn (e. g. Charmander for Charizard);
     * if this pkmn is the lowest evo in its evo line this is set to the name of this pkmn (e. g. Abra for Abra)
     */
    private $lowestEvo;
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
        $pkmnCommons = Constants::$externalPkmnGenCommons->$name;
        $pkmnDiffs = Constants::$externalPkmnGameDiffs->$name;

        parent::__construct($name);

        $this->addCommons($pkmnCommons);
        $this->addDiffs($pkmnDiffs);
    }

    /**
     * Copies values from the JSON obj of this pkmn to this instance.
     * If a must have value is not set in the JSON obj, this throws an AttributeNotFoundException
     * @param StdClass $pkmnDataObj obj from the external JSON data
     *
     * @throws AttributeNotFoundException
     */
    private function addCommons (StdClass $pkmnCommons) {
        $mustHaveCommonsProperties = [
            'id', 'directLearnsets', 'breedingLearnsets', 'eventLearnsets', 'oldGenLearnsets',
            'correctlyWrittenName', 'subpageLinkName'
        ];
        $this->copyProperties($pkmnCommons, $mustHaveCommonsProperties);
    }

    private function copyProperties (StdClass $dataObj, array $propertieNames) {
        foreach ($propertieNames as $property) {
            if (!isset($dataObj->$property)) {
                throw new AttributeNotFoundException($this, $property);
            }
            $this->$property = $dataObj->$property;
        }
    }

    private function addDiffs (StdClass $pkmnDiff) {
        $mustHaveDiffPropertiesToCopy = [
            'unbreedable', 'unpairable', 'lowestEvo', 'evolutions', 'exists',
            'game', 'gender', 'eggGroup1', 'eggGroup2'
        ];
        $this->copyProperties($pkmnDiff, $mustHaveDiffPropertiesToCopy);
        $this->addGameExclusiveLearnsets($pkmnDiff);
    }

    private function addGameExclusiveLearnsets (StdClass $pkmnDiff) {
        $learnsetListNames = [
            'directLearnsets', 'breedingLearnsets', 'eventLearnsets', 'oldGenLearnsets'
        ];
        foreach ($learnsetListNames as $learnsetListName) {
            if (!isset($pkmnDiff->$learnsetListName)) {
                throw new AttributeNotFoundException($this, $learnsetListName);
            }
            $this->$learnsetListName = array_merge($this->$learnsetListName, $pkmnDiff->$learnsetListName);
        }
    }

    private function logLearnsets () {
        Logger::statusLog($this->name.' has learnsets: ');
        Logger::statusLog('direct: '.json_encode($this->directLearnsets));
        Logger::statusLog('breeding: '.json_encode($this->breedingLearnsets));
        Logger::statusLog('event: '.json_encode($this->eventLearnsets));
        Logger::statusLog('old: '.json_encode($this->oldGenLearnsets));
    }

    /**
     * checks Level, TMTR and Tutor learnsets
     */
    public function canLearnDirectly (): bool {
        $directlyLearnability = $this->checkLearnsetType($this->directLearnsets, 'directly');
        if ($directlyLearnability) {
            Logger::statusLog($this->name.' can learn the move directly');
        }
        return $directlyLearnability;
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

    public function getId (): string {
        return $this->id;
    }

    public function canLearnByBreeding (): bool {
        if ($this->unbreedable) {
            return false;
        }

        $breedingLearnability = $this->checkLearnsetType($this->breedingLearnsets, 'breeding');
        if ($breedingLearnability) {
            Logger::statusLog($this->name.' can inherit the move');
        }
        return $breedingLearnability;
    }

    public function canLearnByEvent (): bool {
        $eventLearnability = $this->checkLearnsetType($this->eventLearnsets, 'event');
        if ($eventLearnability) {
            Logger::statusLog($this->name.' can learn the move via event');
        }
        return $eventLearnability;
    }

    public function canLearnByOldGen (): bool {
        $oldGenLearnability = $this->checkLearnsetType($this->oldGenLearnsets, 'old gen');
        if ($oldGenLearnability) {
            Logger::statusLog($this->name.' can learn the move via old gen');
        }
        return $oldGenLearnability;
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
        return $this->lowestEvo === $this->name;
    }

    public function getLowestEvo (): string {
        return $this->lowestEvo;
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

    public function getEvolutions (): array {
        return $this->evolutions;
    }

    public function existsInThisGame (): bool {
        return $this->exists;
    }

    public function getArticleLinkSuperPageName (): string {
        return $this->subpageLinkName;
    }

    public function getCorrectlyWrittenName (): string {
        return $this->correctlyWrittenName;
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