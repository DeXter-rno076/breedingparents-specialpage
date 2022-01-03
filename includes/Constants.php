<?php
class Constants {
	public static StdClass $pkmnData;
	public static StdClass $eggGroups;
	public static Array $unbreedable;
	public static String $targetPkmn;
	public static String $targetMove;
	public static String $targetGen;
	public static OutputPage $out;
    public static bool $displayDebuglogs = false;
    public static bool $displayStatuslogs = false;
    public const auskunftLink = '<a'
        .' href="https://www.pokewiki.de/Pok%C3%A9Wiki:Auskunft">Auskunft</a>';

	//space between each pkmn icon
	public const PKMN_MARGIN_HORI = 200;
    public const PKMN_ICON_LINE_MARGIN = 10;
    public const SVG_SAFETY_MARGIN = 400;
    public const SVG_RECT_PADDING = 5;
    public const SVG_PKMN_SAFETY_MARGIN = 10;
    public const SVG_CIRCLE_MARGIN = 8;

    //todo clean this out plainOut mess up
	public static function out (String $msg) {
		Constants::$out->addHTML(Constants::$out->parseAsContent($msg.'<br />'));
        //maybe rather use e.g. addElement
        //just throwing everything throug parseAsContent might be fine
	}

    public static function plainOut (string $msg) {
        Constants::$out->addHTML($msg.'<br />');
    }
}