<?php
require_once __DIR__.'/../Logger.php';
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../exceptions/AttributeNotFoundException.php';
require_once 'PkmnData.php';

class SuccessorFilter {
    private Array $eggGroupBlacklist;

    /**
     * @var String egg group whose pkmn are checked and added after this filter did its job
     * using a whitelist variable seems unnecessarily complex
     * but is necessary (at least i haven't found a better solution).
     * Just adding the egg group after filtering the successors would work
     * for almost all nodes, BUT not for the root. There the first handled
     * egg group could include the second egg group which would result in
     * redundancies or a wrong tree section.
     */
    private String $whitelistedEggGroup;

    private Array $successorList;

    public function __construct (
        Array $eggGroupBlacklist,
        String $whitelistedEggGroup,
    ) {
        Logger::statusLog('creating SuccessorFilter instance with: '
            .'eggGroupBlacklist: '.json_encode($eggGroupBlacklist)
            .'whiteListedEggGroup: '.json_encode($whitelistedEggGroup));
        $this->eggGroupBlacklist = $eggGroupBlacklist;
        $this->whitelistedEggGroup = $whitelistedEggGroup;

        if (isset(Constants::$eggGroups->$whitelistedEggGroup)) {
            $this->successorList = Constants::$eggGroups->$whitelistedEggGroup;
        } else {
            $this->successorList = [];
            Logger::elog('egg group '.$whitelistedEggGroup.' is not set');
        }
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

    /**
     * Removes all pkmn that are unpairable.
     * This filter is supposed to only let those pkmn through that could be a suiting breeding parent.
     * Pkmn that can't be paired can't possible be a parent.
     */
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

    /**
     * Checks filters that only apply in some gens, like only male pkmn can pass on moves up to gen 5.
     */
    private function checkGenSpecificRequirements () {
        Logger::statusLog('removing by gen specific requirements');
        if (Constants::$targetGen < 6) {
            Logger::statusLog('targetGen is < 6 => checking genders');
            //in gens 2-5 only fathers can give moves to their kids
            $this->removeFemaleOnlys();
        }
    }

    private function removeFemaleOnlys () {
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

    /**
     * Removes all successors for which $condition returns true.
     * @param mixed $condition - function that returns a boolean and determines whether the given pkmn shall be removed
     * 
     */
    private function remove ($condition) {
        for ($i = 0; $i < count($this->successorList); $i++) {

            $pkmn = $this->successorList[$i];
            if ($condition($pkmn)) {
                array_splice($this->successorList, $i, 1);
                $i--;
            }

        }
    }
}