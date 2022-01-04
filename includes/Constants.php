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
    public const SVG_CIRCLE_MARGIN = 8;
    public const SVG_SAFETY_MARGIN = 50;
    public const SVG_OFFSET = 50;

    //todo clean this out plainOut mess up
    public static function out (String $msg) {
        Constants::$out->addWikiTextAsContent($msg.' ');
        //maybe rather use e.g. addElement
        //just throwing everything throug parseAsContent might be fine
    }

    public static function directOut (string $msg) {
        Logger::wlog('calling directOut to directly output HTML. This is dangerous but might be necessary.');
        Constants::$out->addHTML($msg.'<br />');
    }

    public static function error (Exception $e) {
        Constants::plainOut(Constants::$specialPage->msg(
            'breedingparents-error').$e);
    }

    public static function outputOnce (string $msg) {
        static $alreadyCalled = [];
        if (!isset($alreadyCalled[$msg])) {
            $alreadyCalled[$msg] = 1;
            Constants::out($msg);
        }
    }
}