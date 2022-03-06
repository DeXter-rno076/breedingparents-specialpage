<?php
class Constants {
	public static StdClass $externalPkmnJSON;
	public static StdClass $externalEggGroupsJSON;

	public static string $targetPkmnName;
	public static string $targetMoveName;
	public static string $targetGenNumber;

	public static OutputPage $centralOutputPageInstance;
	public static SpecialPage $centralSpecialPageInstance;
	
	/**
	 * @var bool whether to display custom error and warning logs and performance measurements
	 */
	public static bool $displayDebuglogs = false;
	public static bool $displayStatuslogs = false;

	public const PKMN_MARGIN_HORIZONTAL = 200;
	public const PKMN_ICON_LINE_MARGIN = 10;
	public const SVG_RECTANGLE_PADDING = 5;
	public const SVG_PKMN_SAFETY_MARGIN = 10;
	public const SVG_CIRCLE_MARGIN = 4;
	public const SVG_CIRCLE_LINE_WIDTH = 2;
	public const SVG_SAFETY_MARGIN = 50;
	public const SVG_OFFSET = 50;

	public static function i18nMsg (string $msgIdentifier, ...$params): string {
		return Constants::$centralSpecialPageInstance->msg($msgIdentifier, ...$params);
	}

	/**
	 * the 'default' string only ouptut method intendedly uses addWikiTextAsContent()
	 * which parses the string as MediaWiki markdown because adding something as plain HTML
	 * always feels risky af. HTMLForm is the only spot where direct HTML is added to the output
	 * because the class has built in cleaning functionality and so on, which ensures that no
	 * satanic sins are injected.
	 */
	public static function out (string $msg) {
		Constants::$centralOutputPageInstance->addWikiTextAsContent($msg.' ');
	}
}