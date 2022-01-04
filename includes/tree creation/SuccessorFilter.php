<?php
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';
require_once 'PkmnData.php';

class SuccessorFilter {
    /*
    has a function similar to a real world filter
    takes a list of possible successors (i. e. an egg group list)
    and removes every pkmn that isn't compatible to the situation
        generally all pkmn that can't breed
        pkmn that only have one gender aren't an option in some cases
    */
    private Array $eggGroupBlacklist;
    /**
     * using a whitelist variable seems unnecessarily complex
     * but is necessary (at least i haven't found a better solution).
     * Just adding the egg group after filtering the successors would work
     * for almost all nodes, BUT not for the root. there the first handled
     * egg group could include the second egg group which would result in
     * redundancies or a wrong tree section.
     */
    private String $whitelistedEggGroup;
    private Array $successorList;

    public function __construct (
        Array $eggGroupBlacklist,
        String $whitelistedEggGroup,
        Array $successorList
    ) {
        Logger::statusLog('creating SuccessorFilter instance with: '
            .'eggGroupBlacklist: '.json_encode($eggGroupBlacklist)
            .'whiteListedEggGroup: '.json_encode($whitelistedEggGroup)
            .'successorList: '.json_encode($successorList));
        $this->eggGroupBlacklist = $eggGroupBlacklist;
        $this->whitelistedEggGroup = $whitelistedEggGroup;
        $this->successorList = $successorList;
    }

    public function filter (): Array {
        Logger::statusLog('filtering successor list');
        $this->removeBlacklistedPkmn();
        $this->removeUnpairables();
        $this->checkGenSpecificRequirements();
        Logger::statusLog('successor list after: '.json_encode($this->successorList));

        return $this->successorList;
    }

    private function removeBlacklistedPkmn () {
        $pkmnIsBlacklisted = function (string $pkmn): bool {
            $pkmnData = null;
            try {
                $pkmnData = new PkmnData($pkmn);
            } catch (AttributeNotFoundException $e) {
                Constants::error($e);
                return true;
            }

            $isWhitelisted = function (string $eggGroup, string $pkmnName): bool {
                return $this->whitelistedEggGroup === $eggGroup;
            };
            $isBlacklisted = function (string $eggGroup, string $pkmnName): bool {
                return in_array($eggGroup, $this->eggGroupBlacklist);
            };

            if ($isWhitelisted($pkmnData->getEggGroup1(), $pkmn)) {
                return false;
            }
            if ($isBlacklisted($pkmnData->getEggGroup1(), $pkmn)) {
                return true;
            }
            if ($pkmnData->hasSecondEggGroup()) {
                if ($isWhitelisted($pkmnData->getEggGroup2(), $pkmn)) {
                    return false;
                }
                return $isBlacklisted($pkmnData->getEggGroup2(), $pkmn);
            }
            return false;
        };

        $this->remove($pkmnIsBlacklisted);
    }

    private function removeUnpairables () {
        Logger::statusLog('removing unpairable pkmn');
        /*unpairable pkmn cant get children
        => may only appear at the end of a chain*/
        $isUnpairable = function (string $pkmnName): bool {
            $pkmnData = null;
            try {
                $pkmnData = new PkmnData($pkmnName);
            } catch (AttributeNotFoundException $e) {
                Constants::error($e);
                return true;
            }
            $unpairableStatus = $pkmnData->getUnpairable();
            return $unpairableStatus;
        };

        $this->remove($isUnpairable);
    }

    private function checkGenSpecificRequirements () {
        Logger::statusLog('removing by gen specific requirements');
        if (Constants::$targetGen < 6) {
            Logger::statusLog('targetGen is < 6 => checking genders');
            //in gens 2-5 only fathers can give moves to their kids
            $this->checkGender();
        }
    }

    private function checkGender () {
        Logger::statusLog('removing only female pkmn');
        $isFemale = function (string $pkmn): bool {
            $pkmnData = null;
            try {
                $pkmnData = new PkmnData($pkmn);
            } catch (AttributeNotFoundException $e) {
                Constants::error($e);
                return true;
            }
            $gender = $pkmnData->getGender();
            //female pkmn cant pass on moves from gen 2 - 5
            $isFemale = $gender === 'female';
            return $isFemale;
        };

        $this->remove($isFemale);
    }

    private function remove ($condition) {
        for($i = 0; $i < count($this->successorList); $i++) {
            $pkmn = $this->successorList[$i];
            if ($condition($pkmn)) {
                array_splice($this->successorList, $i, 1);
                $i--;
            }
        }
    }
}