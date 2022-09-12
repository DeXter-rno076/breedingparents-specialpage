<?php
class LearnabilityStatus {
    private $learnsDirectly = false;
    private $couldLearnByBreeding = false;
    private $learnsByBreeding = false;
    private $learnsByEvent = false;
    private $learnsByOldGen = false;
    private $learnsFromEvo = false;

    public function canLearn (): bool {
        return $this->learnsDirectly || $this->learnsByBreeding 
        	|| $this->learnsByEvent || $this->learnsByOldGen
            || $this->learnsFromEvo;
    }

    /**
     * learnability info encoded as a string
     * char is set -> can learn in that way
     * d - can learn directly
     * b - can learn by breeding
     * o - can learn in old gen
     * e - can lern by event
     * m - maybe learns by breeding (could inherit)
     * c - learns from evo (learns as a *c*hild (yes, I know, but event was faster))
     */
    public function buildLearnabilityCode (): string {
        $code = '';
        if ($this->learnsDirectly) $code .= 'd';
        if ($this->learnsByBreeding) $code .= 'b';
        if ($this->learnsByOldGen) $code .= 'o';
        if ($this->learnsByEvent) $code .= 'e';
        if ($this->couldLearnByBreeding) $code .= 'm';
        if ($this->learnsFromEvo) $code .= 'c';

        return $code;
    }

    public function setLearnsDirectly () {
        $this->learnsDirectly = true;
    }
    
    public function setCouldLearnByBreeding () {
    	$this->couldLearnByBreeding = true;
    }

    public function setLearnsByBreeding () {
        $this->learnsByBreeding = true;
    }

    public function setLearnsByEvent () {
        $this->learnsByEvent = true;
    }

    public function setLearnsByOldGen () {
        $this->learnsByOldGen = true;
    }

    public function setLearnsFromEvo () {
        $this->learnsFromEvo = true;
    }

    public function getLearnsByEvent (): bool {
        return $this->learnsByEvent;
    }

    public function getLearnsByOldGen (): bool {
        return $this->learnsByOldGen;
    }

    public function getLearnsDirectly (): bool {
        return $this->learnsDirectly;
    }

    public function getCouldLearnByBreeding (): bool {
    	return $this->couldLearnByBreeding;
    }

    public function getLearnsByBreeding (): bool {
        return $this->learnsByBreeding;
    }

    public function getLearnsFromEvo (): bool {
        return $this->learnsFromEvo;
    }
}
