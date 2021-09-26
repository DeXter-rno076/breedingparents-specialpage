<?php
class Constants {
	public static StdClass $pkmnData;
	public static StdClass $eggGroups;
	public static Array $unbreedable;
	public static String $targetPkmn;
	public static String $targetMove;
	public static String $targetGen;
	public static $out;
	public static bool $debugMode = false;

	//space between each pkmn icon
	public const PKMN_MARGIN_HORI = 200;

	public static function out (String $msg) {
		Constants::$out->addHTML($msg."<br />");
	}

	public static function debug (String $msg) {
		if (!Constants::$debugMode) {
			return;
		}

		Constants::out($msg);
	}
}