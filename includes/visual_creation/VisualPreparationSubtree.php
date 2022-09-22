<?php
require_once __DIR__.'/../tree_creation/BreedingSubtree.php';
require_once __DIR__.'/../Logger.php';
require_once 'VisualPreparationNode.php';
require_once __DIR__.'/../tree_creation/PkmnData.php';
require_once __DIR__.'/../Constants.php';

class VisualPreparationSubtree {
    private $successors = [];
    private $visualRoots = [];
    private $subtreeHeight;

    public function __construct (BreedingSubtree $breedingSubtree) {
        foreach ($breedingSubtree->getRoots() as $breedingRoot) {
            $this->visualRoots[] = new VisualPreparationNode($breedingRoot);
        }
        foreach ($breedingSubtree->getSuccessors() as $successor) {
            $successorSubtree = new VisualPreparationSubtree($successor);
            $this->addSuccessor($successorSubtree);
        }
    }

    public function prep () {
        $timeStart = hrtime(true);
        $this->setIconData();
        $timeEnd = hrtime(true);
        $timeDiff = ($timeEnd - $timeStart) / 1000000000;
        Logger::outputDebugMessage('loading icons (part of preparing visual tree) needed: '.$timeDiff.'s');
        //todo some prep methods need subtree height -> somehow enforce correct order of calls
        $this->calcAndSetSubtreeHeight();

        $this->orderSuccessors();

        $this->setYCoords(0);
        $this->setXCoords();
    }

    private function setIconData () {
        foreach ($this->visualRoots as $root) {
            $root->setIconData();
        }
        foreach ($this->successors as $successorSubtrees) {
            $successorSubtrees->setIconData();
        }
    }

    private function calcAndSetSubtreeHeight (): int {
        $successorsTotalHeight = $this->calcSuccessorsHeight();
        $rootsTotalHeight = $this->calcRootsHeight();

        $this->subtreeHeight = max($successorsTotalHeight, $rootsTotalHeight);
        Logger::statusLog('calculated subtreeheight '.$this->subtreeHeight.' for '.$this);
        return $this->subtreeHeight;
    }

    private function calcSuccessorsHeight (): int {
        $totalHeight = 0;
        foreach ($this->successors as $successor) {
            $totalHeight += $successor->calcAndSetSubtreeHeight();
        }
        return $totalHeight;
    }

    private function calcRootsHeight (): int {
        $totalHeight = 0;
        foreach ($this->visualRoots as $root) {
            $totalHeight += $root->calcHeight();
        }
        return $totalHeight;
    }

    public function hasSuccessors (): bool {
        return count($this->successors) > 0;
    }

    private function orderSuccessors () {
        if (count($this->successors) > 1) {
            Logger::statusLog('ordering succesors of '.$this);
            $this->sortSuccessors();
            $this->changeOrderToFromMiddleToOuterLayers();
        } else {
            Logger::statusLog('not ordering succesors of '.$this.', less then 2 successors -> unnecessary');
        }

        foreach ($this->successors as $successor) {
            $successor->orderSuccessors();
        }
    }

    private function sortSuccessors () {
        usort($this->successors, function ($first, $second) {
            return $first->getSortingQuantity() <=> $second->getSortingQuantity();
        });
    }

    //used by usort call in sortSuccessors
    private function getSortingQuantity (): int {
        //this pushes old gen learning pkmn outside
        if ($this->visualRoots[0]->getDisplayOldGenMarker()) {
            return $this->subtreeHeight + 1;
        } else {
            return $this->subtreeHeight;
        }
    }

    /**
     * short draft: the smaller the sub tree, the closer to the middle
     * sortSuccessors sorts all successors descending
     * changeOrderToFromMiddleToOuterLayern starts from the end and jumps 2 indices at a time to the start
     * makes a u turn, steps one index further and hops 2 indices at a time to the end
     * by pushing a successor at every jump to a list, the successors are reodered as wanted
     */
    private function changeOrderToFromMiddleToOuterLayers () {
        Logger::statusLog('changing sorting order for '.$this.', '.count($this->successors).' successors');
        $middleToOuterOrdering = [];
        $pointer = $this->getSuccessorOrderingBackPointer();
        Logger::statusLog('starting pointer from back: '.$pointer);

        for (; $pointer >= 0; $pointer -= 2) {
            Logger::statusLog('new pointer: '.$pointer);
            $middleToOuterOrdering[] = $this->successors[$pointer];
        }
        $pointer += 2;
        Logger::statusLog('corrected pointer to '.$pointer.' after first run');

        for ($pointer++ ; $pointer < count($this->successors); $pointer += 2) {
            Logger::statusLog('new pointer: '.$pointer);
            $middleToOuterOrdering[] = $this->successors[$pointer];
        }

        $this->successors = $middleToOuterOrdering;
    }

    private function getSuccessorOrderingBackPointer (): int {
        $successorsAmount = count($this->successors);
        $secondLastSuccessorIndex = $successorsAmount - 2;
        $lastSuccessorIndex = $successorsAmount - 1;

        if ($successorsAmount % 2 === 0) {
            return $secondLastSuccessorIndex;
        } else {
            return $lastSuccessorIndex;
        }
    }

    //todo split up (too long method)
    private function setYCoords (int $offset): int {
        if (count($this->visualRoots) === 1) {
            $root = $this->visualRoots[0];
            $nodeHeight = $root->calcHeight();
            $root->setY($offset + ($this->subtreeHeight - $root->calcHeight())/2);
            Logger::statusLog('calculated y = '.$root->getY().' for '.$root
                .', subtree height = '.$this->subtreeHeight.', offset = '.$offset);
        }

        $currentSuccessorOffset = $this->calcInitialSuccessorOffset($offset);
        foreach ($this->successors as $subtreeSuccessor) {
            $currentSuccessorOffset += $subtreeSuccessor->setYCoords($currentSuccessorOffset);
        }

        if (count($this->visualRoots) > 1) {
            Logger::statusLog('calculating y coordinates for roots of '.$this);
            if (count($this->successors) === 0) {
                throw new InvalidStateException('breeding subtree with multiple roots has no successors');
            }
            $rootsHeight = $this->calcRootsHeight();
            $rootsAreWiderThanSuccessors = $rootsHeight > $this->getSuccessorsHeight();

            $top = 0;
            if ($rootsAreWiderThanSuccessors) {
                Logger::statusLog('roots are wider than successors');
                $top = $offset;
            } else {
                Logger::statusLog('successors are wider than roots');
                $top = $this->getHighestSuccessorYCoordinate();
            }

            $bottom = 0;
            if ($rootsAreWiderThanSuccessors) {
                $bottom = $offset + $rootsHeight;
            } else {
                $bottom = $this->getLowestSuccessorYCoordinate();
            }

            //todo this assumes all roots have the same height
            $nodeHeight = $this->visualRoots[0]->calcHeight();
            $height = $bottom - $top;
            $rootsOffset = $offset + ($height - count($this->visualRoots)*$nodeHeight)/2;

            Logger::statusLog('offset='.$offset.',top y='.$top.', bottom y='.$bottom.', node height='.$nodeHeight
                .', total height='.$height.' => calculated roots offset='.$rootsOffset);

            for ($i = 0; $i < count($this->visualRoots); $i++) {
                $root = $this->visualRoots[$i];
                $root->setY($rootsOffset + $i*$nodeHeight);
                Logger::statusLog('y = '.$root->getY().' for '.$root->getName());
            }
        }

        return $this->subtreeHeight;
    }

    private function getSuccessorsHeight (): int {
        $totalHeight = 0;
        foreach ($this->successors as $successor) {
            $totalHeight += $successor->subtreeHeight;
        }
        return $totalHeight;
    }

    private function calcInitialSuccessorOffset (int $offset): int {
        $successorsHeightSum = $this->getSuccessorsHeight();
        return $offset + ($this->subtreeHeight - $successorsHeightSum)/2;
    }

    private function getHighestSuccessorYCoordinate (): int {
        if (count($this->successors) === 0) {
            return PHP_INT_MAX;
        }
        $firstSuccessor = $this->successors[0];
        $firstSuccessorRoot = $firstSuccessor->getRoots()[0];
        return min($firstSuccessorRoot->getY(), $firstSuccessor->getHighestSuccessorYCoordinate());
    }

    private function getLowestSuccessorYCoordinate (): int {
        if (count($this->successors) === 0) {
            return 0;
        }
        $lastSuccessor = $this->successors[count($this->successors) - 1];
        $lastSuccessorRoots = $lastSuccessor->getRoots();
        $lastSuccessorRoot = $lastSuccessorRoots[count($lastSuccessorRoots) - 1];
        return max($lastSuccessorRoot->getBottomY(), $lastSuccessor->getLowestSuccessorYCoordinate());
    }

    private function setXCoords (int $deepness = 0) {
        foreach ($this->visualRoots as $root) {
            $root->calcAndSetCenteredXCoordinate($deepness);
        }

        foreach ($this->successors as $successor) {
            $successor->setXCoords($deepness + 1);
        }
    }

    private function addSuccessor (VisualPreparationSubtree $subtree) {
        $this->successors[] = $subtree;
    }

    public function getDepth (): int {
        $highestDepth = 0;
        foreach ($this->successors as $successor) {
            $successorDepth = $successor->getDepth();
            if ($successorDepth > $highestDepth) {
                $highestDepth = $successorDepth;
            }
        }

        Logger::statusLog('new highest depth '.$highestDepth);

        return $highestDepth + 1;
    }

    public function getSubtreeHeight (): int {
        return $this->subtreeHeight;
    }

    public function getRoots (): array {
        return $this->visualRoots;
    }

    public function getSuccessors (): array {
        return $this->successors;
    }

    public function getFirstPkmnSuccessor (): VisualPreparationNode {
        foreach ($this->successors as $successor) {
            $firstPkmnRoot = $successor->getFirstPkmnRoot();
            if (!is_null($firstPkmnRoot)) {
                return $firstPkmnRoot;
            }
        }
    }

    public function getFirstPkmnRoot (): VisualPreparationNode {
        foreach ($this->visualRoots as $root) {
            if (Constants::isPkmn($root->getName())) {
                return $root;
            }
        }
    }

    /**
     * todo find a cleaner implementation than getfirstpkmnsuccessor
     * todo evoconnection methods are a connection to pokemon
     * @throws AttributeNotFoundException
     */
    public function hasEvoConnection (): bool {
        $firstSuccessor = $this->getFirstPkmnSuccessor();
        if (is_null($firstSuccessor)) {
            return false;
        }
        $firstSuccessorJSONData = PkmnData::cachedConstruct($firstSuccessor->getName());

        //evo connections always have one root and one successor
        $firstRoot = $this->visualRoots[0];
        if (is_null($firstRoot)) {
            return false;
        }


        $firstSuccessorIsLowestEvoOfThis = $firstSuccessorJSONData->isLowestEvolution()
            && $firstSuccessorJSONData->hasAsEvolution($firstRoot->getName());

        return $firstRoot->isRoot()
            && $firstSuccessor->isRoot() && $firstSuccessorIsLowestEvoOfThis;
    }

    public function getLogInfo (): string {
        $str = 'VisualPreparationSubtree';
        foreach ($this->visualRoots as $root) {
            $str .= '-'.$root->getName();
        }
        return $str.';;';
    }

    public function __toString (): string {
        return $this->getLogInfo();
    }
}