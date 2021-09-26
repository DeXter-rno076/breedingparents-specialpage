<?php
abstract class PkmnObj {
	protected String $name = '';
	protected Array $successors = [];
	protected bool $learnsByEvent = false;

	protected String $iconUrl = '';
	protected int $iconWidth = -1;
	protected int $iconHeight = -1;

	protected String $fileError = '';

	public function getName (): String {
		return $this->name;
	}

	public function addSuccessor (PkmnObj $successor) {
		$this->successors[] = $successor;
	}

	public function getSuccessors (): Array {
		return $this->successors;
	}

	public function setLearnsByEvent () {
		$this->learnsByEvent = true;
	}

	public function getLearnsByEvent (): bool {
		return $this->learnsByEvent;
	}

	public function getLearnsByOldGen (): bool {
		return false;
		//more coming soon
	}

	//=========================================================
	//icon stuff

	public function setIconUrl (String $url) {
		$this->iconUrl = $url;
	}

	public function getIconUrl (): String {
		return $this->iconUrl;
	}

	public function setIconWidth (int $width) {
		$this->iconWidth = $width;
	}

	public function getIconWidth (): int {
		return $this->iconWidth;
	}

	public function setIconHeight (int $height) {
		$this->iconHeight = $height;
	}

	public function getIconHeight (): int {
		return $this->iconHeight;
	}

	public function setFileError (String $e) {
		$this->fileError = $e;
	}

	public function getFileError (): String {
		return $this->fileError;
	}
}