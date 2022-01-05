<?php
class Constants {
    public static StdClass $pkmnData;
    public static StdClass $eggGroups;
    public static String $targetPkmn;
    public static String $targetMove;
    public static String $targetGen;
    public static OutputPage $out;
    public static SpecialPage $specialPage;
    public static bool $displayDebuglogs = false;
    public static bool $displayStatuslogs = false;

    //space between each pkmn icon
    public const PKMN_MARGIN_HORI = 200;
    public const PKMN_ICON_LINE_MARGIN = 10;
    public const SVG_RECT_PADDING = 5;
    public const SVG_PKMN_SAFETY_MARGIN = 10;
    public const SVG_CIRCLE_MARGIN = 6;
    public const SVG_SAFETY_MARGIN = 50;
    public const SVG_OFFSET = 50;

    public static function out (String $msg) {
        Constants::$out->addWikiTextAsContent($msg.' ');
    }


    public static function error (Exception $e) {
        Constants::out(Constants::$specialPage->msg(
            'breedingparents-error', $e));
    }

    public static function outputOnce (string $msg) {
        static $alreadyCalled = [];
        if (!isset($alreadyCalled[$msg])) {
            $alreadyCalled[$msg] = 1;
            Constants::out($msg);
        }
    }
}