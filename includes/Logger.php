<?php
require_once 'Constants.php';

class Logger {
    private static Array $elogCache = [];
    private static Array $wlogCache = [];
    private static Array $statusLogCache = [];

    private function __construct () {}

    public static function outputDebugMessage (string $msg) {
        if (Constants::$displayDebuglogs) {
            Constants::out($msg);
        }
    }

    public static function elog (string $msg) {
        array_push(Logger::$elogCache, $msg);
        array_push(Logger::$statusLogCache, $msg);
    }

    public static function wlog (string $msg) {
        array_push(Logger::$wlogCache, $msg);
        array_push(Logger::$statusLogCache, $msg);
    }

    public static function statusLog (string $msg) {
        array_push(Logger::$statusLogCache, $msg);
    }

    public static function flush () {
        if (Constants::$displayDebuglogs) {
            Logger::printLog('Error log', Logger::$elogCache);
            Logger::printLog('Warning log', Logger::$wlogCache);
        }
        if (Constants::$displayStatuslogs) {
            Logger::printLog('Status log', Logger::$statusLogCache);
        }
    }

    private static function printLog (string $title, Array $log) {
        //sometimes sacrifices have to be made, here it was formatted code
		//lists in MediaWiki markdown want real line breaks, not just <br /> and \n doesnt work for some reason
        $logContainer = '<div class="breedingChainsLogContainer">
'
            .'\'\'\''.$title.'\'\'\'
';
        foreach ($log as $logMsg) {
            $logContainer .= '* '.$logMsg.'
';
        }
        $logContainer .= '</div>';

        Constants::out($logContainer);
    }
}