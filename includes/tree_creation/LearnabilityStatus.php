<?php
class LearnabilityStatus {
	private $learnsDirectly = false;
	private $learnsByBreeding = false;
	private $learnsByEvent = false;
	private $learnsByOldGen = false;

	public function canLearn (): bool {
		return $this->learnsDirectly || $this->learnsByBreeding || $this->learnsByEvent || $this->learnsByOldGen;
	}

	public function setLearnsDirectly () {
		$this->learnsDirectly = true;
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

	public function getLearnsByEvent (): bool {
		return $this->learnsByEvent;
	}

	public function getLearnsByOldGen (): bool {
		return $this->learnsByOldGen;
	}

	public function getLearnsDirectly (): bool {
		return $this->learnsDirectly;
	}

	public function getLearnsByBreeding (): bool {
		return $this->learnsByBreeding;
	}
}