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
}