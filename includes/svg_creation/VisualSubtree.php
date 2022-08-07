<?php
require_once __DIR__.'/../tree_creation/BreedingSubtree.php';
require_once __DIR__.'/../Logger.php';
require_once 'VisualNode.php';
require_once __DIR__.'/../tree_creation/PkmnData.php';
require_once __DIR__.'/../Constants.php';

class VisualSubtree {
    private $successors = [];
    private $visualRoots = [];
    private $subtreeHeight;

    public function __construct (BreedingSubtree $breedingSubtree) {
        foreach ($breedingSubtree->getRoots() as $breedingRoot) {
            $this->visualRoots[] = new VisualNode($breedingRoot);
        }
        foreach ($breedingSubtree->getSuccessors() as $successor) {
            $successorSubtree = new VisualSubtree($successor);
            $this->addSuccessor($successorSubtree);
        }
    }

    public function prep () {
        $this->setIconData();
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
            $totalHeight += $root->calcSingleNodeHeight($root);
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
		Logger::statusLog('successor array of '.$this.' with treeSectionHeightKeys after pure sorting: '
			.json_encode($this->successors));
	}

    private function getSortingQuantity (): int {
        //this pushes old gen learning pkmn outside
        if ($this->visualRoots[0]->getDisplayOldGenMarker()) {
            return $this->subtreeHeight + 1;
        } else {
            return $this->subtreeHeight;
        }
    }

	/**
	 * todo explain this
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

    private function setYCoords (int $offset): int {
        if (count($this->visualRoots) === 1) {
            $root = $this->visualRoots[0];
            $nodeHeight = $root->calcSingleNodeHeight();
            $root->setY($offset + ($this->subtreeHeight - $root->getIconHeight())/2);
        }

        $currentSuccessorOffset = $this->calcInitialSuccessorOffset($offset);
        foreach ($this->successors as $subtreeSuccessor) {
            $currentSuccessorOffset += $subtreeSuccessor->setYCoords($currentSuccessorOffset);
        }

        if (count($this->visualRoots) > 1) {
            //todo successors may be empty
            $top = $this->successors[0]->getRoots()[0]->getY();
            $lastSuccessorsRoots = $this->successors[count($this->successors) - 1]->getRoots();
            $bottom = $lastSuccessorsRoots[count($lastSuccessorsRoots) - 1]->getY();
            $nodeHeight = $this->visualRoots[0]->calcSingleNodeHeight();
            $height = $bottom - $top + $nodeHeight;
            $rootsOffset = $offset + ($height - count($this->visualRoots)*$nodeHeight)/2;

            for ($i = 0; $i < count($this->visualRoots); $i++) {
                $this->visualRoots[$i]->setY($rootsOffset + $i*$nodeHeight);
            }

            for ($i = 0; $i < count($this->visualRoots); $i++) {
                $this->visualRoots[$i]->setY($this->visualRoots[$i]->getY() - $this->visualRoots[$i]->getIconHeight()/2);
            }
        }

        return $this->subtreeHeight;
    }

    private function calcInitialSuccessorOffset (int $offset): int {
        $successorsHeightSum = 0;
        foreach ($this->successors as $successor) {
            $successorsHeightSum += $successor->subtreeHeight;
        }
        
        return $offset + ($this->subtreeHeight - $successorsHeightSum)/2;
    }

	private function setXCoords (int $deepness = 0) {
        foreach ($this->visualRoots as $root) {
            $root->calcAndSetCenteredXCoordinate($deepness);
        }

		foreach ($this->successors as $successor) {
			$successor->setXCoords($deepness + 1);
		}
	}

    private function addSuccessor (VisualSubtree $subtree) {
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

    public function getFirstPkmnSuccessor (): VisualNode {
        foreach ($this->successors as $successor) {
            $firstPkmnRoot = $successor->getFirstPkmnRoot();
            if (!is_null($firstPkmnRoot)) {
                return $firstPkmnRoot;
            }
        }
    }

    public function getFirstPkmnRoot (): VisualNode {
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
		$firstSuccessorJSONData = new PkmnData($firstSuccessor->getName());

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
        return 'VisualSubtree;;';
    }

    public function __toString (): string {
        return $this->getLogInfo();
    }
}