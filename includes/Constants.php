<?php
class Constants {
    public static StdClass $externalPkmnJSON;
    public static StdClass $externalEggGroupsJSON;
    public static String $targetPkmnName;
    public static String $targetMoveName;
    public static String $targetGenNumber;
    public static OutputPage $centralOutputPageInstance;
    public static SpecialPage $centralSpecialPageInstance;
    /**
     * @var bool whether to display custom error and warnung logs, performance measurements and the svg center dot
     */
    public static bool $displayDebuglogs = false;
    /**
     * @var bool whether to display custom status logs
     */
    public static bool $displayStatuslogs = false;

    //space between each pkmn icon
    public const PKMN_MARGIN_HORIZONTAL = 200;
    public const PKMN_ICON_LINE_MARGIN = 10;
    public const SVG_RECTANGLE_PADDING = 5;
    public const SVG_PKMN_SAFETY_MARGIN = 10;
    public const SVG_CIRCLE_MARGIN = 6;
    public const SVG_SAFETY_MARGIN = 50;
    public const SVG_OFFSET = 50;

	public static function i18nMsg (string $msgIdentifier, ...$params): string {
		return Constants::$centralSpecialPageInstance->msg($msgIdentifier, ...$params);
	}

    public static function out (String $msg) {
        Constants::$centralOutputPageInstance->addWikiTextAsContent($msg.' ');
    }

    public static function error (Exception $e) {
		$eMsgForOutput = Constants::getShortenedErrorMsg($e);
		$outputMsg = Constants::i18nMsg('breedingchains-error', $eMsgForOutput);
		Constants::outputAlertMessage($outputMsg);
	}

	private static function getShortenedErrorMsg (Exception $e): string {
		$eMsg = $e->__toString();
		$wantedEndOfErrorMessage = Constants::getWantedEndOfErrorMessage($eMsg);
		return substr($eMsg, 0, $wantedEndOfErrorMessage);
	}

	private static function getWantedEndOfErrorMessage (string $eMsg): int {
		$msgEndMarker = 'Stack trace';
		$msgEndMarkerIndex = strpos($eMsg, $msgEndMarker);
		if (!$msgEndMarkerIndex) {
			return strlen($eMsg);
		}
		return $msgEndMarkerIndex;
	}

    public static function outputOnceAlertMessage (string $msg) {
        static $alreadyCalled = [];
        if (!isset($alreadyCalled[$msg])) {
            $alreadyCalled[$msg] = 1;
			Constants::outputAlertMessage($msg);
        }
    }

	public static function outputInfoMessage (string $msg) {
		Constants::outputMessageBox($msg, 'info');
	}

	public static function outputAlertMessage (string $msg) {
		Constants::outputMessageBox($msg, 'alert');
	}

	private static function outputMessageBox (string $msg, string $boxType) {
		$box = new HTMLElement('div', [
			'class' => Constants::getMessageBoxClass($boxType)
		], [$msg]);
		$box->addToOutput();
	}

	private static function getMessageBoxClass (string $type): string {
		$classes = 'breedingChainsMessageBox';

		switch ($type) {
			case 'alert':
				$classes .= ' breedingChainsAlertMessage';
				break;
			case 'info':
				$classes .= ' breedingChainsInfoMessage';
				break;
		}

		return $classes;
	}
}