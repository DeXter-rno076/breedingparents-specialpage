<?php
class Constants {
	public static $externalPkmnJSON;
	public static $externalEggGroupsJSON;

	public static $targetPkmnName;
	public static $targetMoveName;
	public static $targetGenNumber;

	public static $centralOutputPageInstance;
	public static $centralSpecialPageInstance;
	
	/**
	 * @var bool whether to display custom error and warning logs and performance measurements
	 */
	public static $displayDebuglogs = false;
	public static $displayStatuslogs = false;

	public const PKMN_MARGIN_HORIZONTAL = 200;
	public const PKMN_ICON_LINE_MARGIN = 10;
	public const SVG_RECTANGLE_PADDING = 5;
	public const SVG_PKMN_SAFETY_MARGIN = 10;
	public const SVG_CIRCLE_MARGIN = 4;
	public const SVG_LINE_WIDTH = 2;
	public const SVG_SAFETY_MARGIN = 50;
	public const SVG_OFFSET = 50;
	public const APPROXIMATE_STRING_HEIGHT = 16;
	public const SVG_TEXT_LINE_MARGIN = 4;
	public const SVG_CIRCLE_DIAMETER = 60;

	private static $groupIdCounter = 0;
	public const UNUSED_GROUP_ID = -1;

	public static function generateGroupId (): int {
		return Constants::$groupIdCounter++;
	}

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