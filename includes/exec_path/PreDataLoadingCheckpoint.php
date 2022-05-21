<?php
require_once __DIR__.'/../Constants.php';
require_once 'EasterEggCheckpoint.php';

class PreDataLoadingCheckPoint extends Checkpoint {
	public function __construct () {
		parent::__construct('problem in input data');
	}
	
	public function passOn (): string {
		if ($this->moveIsUnknown()) {
			$this->outputInfoMessage('breedingchains-unknown-move', Constants::$targetMoveNameOriginalInput);
			return $this->terminationCode;
		}

		$externalDataLoadingTrack = new EasterEggCheckPoint();
		return $externalDataLoadingTrack->passOn();
	}

	private function moveIsUnknown (): bool {
		return !in_array(Constants::$targetMoveName, Constants::$MOVE_NAMES);
	}
}