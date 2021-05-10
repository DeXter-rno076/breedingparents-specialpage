<?php
class Constants {
	public static StdClass $pkmnData;
	public static StdClass $eggGroups;
	public static Array $unbreedable;
	public static String $targetPkmn;
	public static String $targetMove;
	public static String $targetGen;
	public static $out;

	public static function out (String $msg) {
		Constants::$out->addHTML($msg."<br />");
	}
}