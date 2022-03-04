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

    public static function out (String $msg) {
        Constants::$centralOutputPageInstance->addWikiTextAsContent($msg.' ');
    }

    public static function error (Exception $e) {
        Constants::out(Constants::$centralSpecialPageInstance->msg(
            'breedingchains-error', Constants::getShortenedErrorMsg($e)));
    }

	private static function getShortenedErrorMsg (string $e): string {
		return substr($e, 0, strpos($e, 'Stack trace'));
	}

    public static function outputOnce (string $msg) {
        static $alreadyCalled = [];
        if (!isset($alreadyCalled[$msg])) {
            $alreadyCalled[$msg] = 1;
            Constants::out($msg);
        }
    }
}