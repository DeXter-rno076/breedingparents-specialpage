<?php
abstract class Constants {
	public static $externalPkmnGenCommons;
	public static $externalPkmnGameDiffs;
	public static $externalEggGroupsJSON;

	public static $targetPkmnName;
	public static $targetMoveName;
	public static $targetGenNumber;
	public static $targetGameString;

	public static $targetPkmnNameNormalCasing;
	public static $targetMoveNameNormalCasing;

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

	public const GAME_LIST = [
		'Pokémon: Legenden Arceus' => 'PLA',
		'Pokémon Leuchtende Perle' => 'LP',
		'Pokémon Strahlender Diamant' => 'SD',
		'Pokémon Schild' => 'SH',
		'Pokémon Schwert' => 'SW',
		'Pokémon Let\'s Go Evoli' => 'LGE',
		'Pokémon Let\'s Go Pikachu' => 'LGP',
		'Pokémon Ultramond' => 'UM',
		'Pokémon Ultrasonne' => 'US',
		'Pokémon Mond' => 'M',
		'Pokémon Sonne' => 'So',
		'Pokémon Alpha Saphir' => 'AS',
		'Pokémon Omega Rubin' => 'OR',
		'Pokémon Y' => 'Y',
		'Pokémon X' => 'X',
		'Pokémon Weiß 2' => 'W2',
		'Pokémon Schwarz 2' => 'S2',
		'Pokémon Weiß' => 'W',
		'Pokémon Schwarz' => 'Sc',
		'Pokémon Silberne Edition SoulSilver' => 'SS',
		'Pokémon Goldende Edition HeartGold' => 'HG',
		'Pokémon Platin' => 'PT',
		'Pokémon Perl' => 'P',
		'Pokémon Diamant' => 'D',
		'Pokémon Blattgrün' => 'BG',
		'Pokémon Feuerrot' => 'FR',
		'Pokémon Smaragd' => 'SM',
		'Pokémon Saphir' => 'SA',
		'Pokémon Rubin' => 'RU',
		'Pokémon Kristall' => 'K',
		'Pokémon Silber' => 'Si',
		'Pokémon Gold' => 'Go',
	];

	public const GAMES_TO_GEN = [
		'Go' => 2,
		'Si' => 2,
		'K' => 2,
		'RU' => 3,
		'SA' => 3,
		'SM' => 3,
		'FR' => 3,
		'BG' => 3,
		'D' => 4,
		'P' => 4,
		'PT' => 4,
		'HG' => 4,
		'SS' => 4,
		'Sc' => 5,
		'W' => 5,
		'S2' => 5,
		'W2' => 5,
		'X' => 6,
		'Y' => 6,
		'OR' => 6,
		'AS' => 6,
		'So' => 7,
		'M' => 7,
		'US' => 7,
		'UM' => 7,
		'LPG' => 7,
		'LGE' => 7,
		'SW' => 8,
		'SH' => 8,
		'SD' => 8,
		'LP' => 8,
		'PLA' => 8,
	];

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
	 * because the class has built with cleaning functionality and so on, which ensures that no
	 * satanic sins are injected.
	 */
	public static function out (string $msg) {
		Constants::$centralOutputPageInstance->addWikiTextAsContent($msg.' ');
	}

	public static function logUserinputConstants () {
		Logger::statusLog('target game: '.Constants::$targetGameString);
		Logger::statusLog('target gen: '.Constants::$targetGenNumber);
		Logger::statusLog('target move input: '.Constants::$targetMoveNameNormalCasing);
		Logger::statusLog('target move lower case: '.Constants::$targetMoveName);
		Logger::statusLog('target pkmn input: '.Constants::$targetPkmnNameNormalCasing);
		Logger::statusLog('target pkmn lower case: '.Constants::$targetPkmnName);
	}
}