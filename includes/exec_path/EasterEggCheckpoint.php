<?php
require_once __DIR__.'/../Constants.php';
require_once __DIR__.'/../output_messages/InfoMessage.php';
require_once 'ExternalDataLoadingTrack.php';

class EasterEggCheckPoint extends Checkpoint {
	public function __construct () {
		parent::__construct('easter egg');
	}
	
	public function passOn (): string {
		if ($this->isGreenchuEasterEgg()) {
			$this->outputGreenchuEasterEggMsg();
			return $this->terminationCode;
		} else if ($this->isDeXterEasterEgg()) {
			$this->outputDeXterEasterEggMsg();
			return $this->terminationCode;
		}

		$externalDataLoadingTrack = new ExternalDataLoadingTrack();
		return $externalDataLoadingTrack->passOn();
	}

	private function isGreenchuEasterEgg (): bool {
		return Constants::$targetPkmnNameOriginalInput === 'Greenchu';
	}

	private function outputGreenchuEasterEggMsg () {
		$this->outputInfoMessage('breedingchains-easteregg-greenchu');
	}

	private function isDeXterEasterEgg ():bool {
		return Constants::$targetPkmnNameOriginalInput === 'DeXter';
	}

	private function outputDeXterEasterEggMsg () {
		$this->outputInfoMessage('breedingchains-easteregg-dexter');
	}
}