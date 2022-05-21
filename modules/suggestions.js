//!doesnt work in firefox mobile for some reasons

const pkmnInputContainer = document.getElementById('mw-input-targetPkmn');
const pkmnTextInputBox = pkmnInputContainer.firstChild;
const moveInputContainer = document.getElementById('mw-input-targetMove');
const moveTextInputBox = moveInputContainer.firstChild;
const gameInputContainer = document.getElementById('mw-input-targetGame');
const gameTextInputBox = gameInputContainer.firstChild;
const moveSuggestionsTag = buildSuggestions('targetMoveSuggestions', []);

const GAME_TO_SK = {
	'Pokémon: Legenden Arceus': 'PLA',
	'Pokémon Leuchtende Perle': 'LP',
	'Pokémon Strahlender Diamant': 'SD',
	'Pokémon Schild': 'SH',
	'Pokémon Schwert': 'SW',
	'Pokémon Let\'s Go Evoli': 'LGE',
	'Pokémon Let\'s Go Pikachu': 'LGP',
	'Pokémon Ultramond': 'UM',
	'Pokémon Ultrasonne': 'US',
	'Pokémon Mond': 'M',
	'Pokémon Sonne': 'So',
	'Pokémon Alpha Saphir': 'AS',
	'Pokémon Omega Rubin': 'OR',
	'Pokémon Y': 'Y',
	'Pokémon X': 'X',
	'Pokémon Weiß 2': 'W2',
	'Pokémon Schwarz 2': 'S2',
	'Pokémon Weiß': 'W',
	'Pokémon Schwarz': 'Sc',
	'Pokémon Silberne Edition SoulSilver': 'SS',
	'Pokémon Goldende Edition HeartGold': 'HG',
	'Pokémon Platin': 'PT',
	'Pokémon Perl': 'P',
	'Pokémon Diamant': 'D',
	'Pokémon Blattgrün': 'BG',
	'Pokémon Feuerrot': 'FR',
	'Pokémon Smaragd': 'SM',
	'Pokémon Saphir': 'SA',
	'Pokémon Rubin': 'RU',
	'Pokémon Kristall': 'K',
	'Pokémon Silber': 'Si',
	'Pokémon Gold': 'Go',
	'':'',
};
const GAME_SUGGESTIONS_LIST = [
	'Pokémon: Legenden Arceus',
	['PLA', 'Pokémon: Legenden Arceus'],
	['Legenden Arceus', 'Pokémon: Legenden Arceus'],
	'Pokémon Leuchtende Perle',
	['Leuchtende Perle', 'Pokémon Leuchtende Perle'],
	['LP', 'Pokémon Leuchtende Perle'],
	'Pokémon Strahlender Diamant',
	['Strahlender Diamant', 'Pokémon Strahlender Diamant'],
	['SD', 'Pokémon Strahlender Diamant'],
	'Pokémon Schild',
	['Schild', 'Pokémon Schild'],
	['SH', 'Pokémon Schild'],
	'Pokémon Schwert',
	['Schwert', 'Pokémon Schwert'],
	['SW', 'Pokémon Schwert'],
	'Pokémon Let\'s Go Evoli',
	['Let\'s Go Evoli', 'Pokémon Let\'s Go Evoli'],
	['LGE', 'Pokémon Let\'s Go Evoli'],
	'Pokémon Let\'s Go Pikachu',
	['Let\'s Go Pikachu', 'Pokémon Let\'s Go Pikachu'],
	['LGP', 'Pokémon Let\'s Go Pikachu'],
	'Pokémon Ultramond',
	['Ultramond', 'Pokémon Ultramond'],
	['UM', 'Pokémon Ultramond'],
	'Pokémon Ultrasonne',
	['Ultrasonne', 'Pokémon Ultrasonne'],
	['US', 'Pokémon Ultrasonne'],
	'Pokémon Mond',
	['Mond', 'Pokémon Mond'],
	['M', 'Pokémon Mond'],
	'Pokémon Sonne',
	['Sonne', 'Pokémon Mond'],
	['So', 'Pokémon Mond'],
	'Pokémon Alpha Saphir',
	['Alpha Saphir', 'Pokémon Alpha Saphir'],
	['AS', 'Pokémon Alpha Saphir'],
	'Pokémon Omega Rubin',
	['Omega Rubin', 'Pokémon Omega Rubin'],
	['OR', 'Pokémon Omega Rubin'],
	'Pokémon Y',
	['Y', 'Pokémon Y'],
	'Pokémon X',
	['X', 'Pokémon X'],
	'Pokémon Weiß 2',
	['Weiß 2', 'Pokémon Weiß 2'],
	['W2', 'Pokémon Weiß 2'],
	'Pokémon Schwarz 2',
	['Schwarz 2', 'Pokémon Weiß 2'],
	['S2', 'Pokémon Weiß 2'],
	'Pokémon Weiß',
	['Weiß', 'Pokémon Weiß'],
	['W', 'Pokémon Weiß'],
	'Pokémon Schwarz',
	['Schwarz', 'Pokémon Schwarz']
	['Sc', 'Pokémon Schwarz'],
	'Pokémon Silberne Edition SoulSilver',
	['Pokémon SoulSilver', 'Pokémon Silberne Edition SoulSilver'],
	['SoulSilver', 'Pokémon Silberne Edition SoulSilver'],
	['SS', 'Pokémon Silberne Edition SoulSilver'],
	'Pokémon Goldene Edition HeartGold',
	['Pokémon HeartGold', 'Pokémon Goldene Edition HeartGold'],
	['HeartGold', 'Pokémon Goldene Edition HeartGold'],
	['HG', 'Pokémon Goldene Edition HeartGold'],
	'Pokémon Platin',
	['Platin', 'Pokémon Platin'],
	['PT', 'Pokémon Platin'],
	'Pokémon Perl',
	['Perl', 'Pokémon Perl'],
	['P', 'Pokémon Perl'],
	'Pokémon Diamant',
	['Diamant', 'Pokémon Diamant'],
	['D', 'Pokémon Diamant'],
	'Pokémon Blattgrün',
	['Blattgrün', 'Pokémon Blattgrün'],
	['BG', 'Pokémon Blattgrün'],
	'Pokémon Feuerrot',
	['Feuerrot', 'Pokémon Feuerrot'],
	['FR', 'Pokémon Feuerrot'],
	'Pokémon Smaragd',
	['Smaragd', 'Pokémon Smaragd'],
	['SM', 'Pokémon Smaragd'],
	'Pokémon Saphir',
	['Saphir', 'Pokémon Saphir'],
	['SA', 'Pokémon Saphir'],
	'Pokémon Rubin',
	['Rubin', 'Pokémon Rubin'],
	['RU', 'Pokémon Rubin'],
	'Pokémon Kristall',
	['Kristall', 'Pokémon Kristall'],
	['K', 'Pokémon Kristall'],
	'Pokémon Silber',
	['Silber', 'Pokémon Silber'],
	['Si', 'Pokémon Silber'],
	'Pokémon Gold',
	['Gold', 'Pokémon Gold'],
	['Go', 'Pokémon Gold']
];

pkmnTextInputBox.addEventListener('change', switchMoveSuggestions);
gameTextInputBox.addEventListener('change', switchMoveSuggestions);

initDatalists();
initSuggestions();

function initDatalists () {
	pkmnTextInputBox.setAttribute('list', 'targetPkmnSuggestions');
	pkmnInputContainer.appendChild(buildSuggestions('targetPkmnSuggestions', PKMN_SUGGESTIONS));
	moveTextInputBox.setAttribute('list', 'targetMoveSuggestions');
	moveInputContainer.appendChild(moveSuggestionsTag);
	gameTextInputBox.setAttribute('list', 'targetGameSuggestions');
	gameInputContainer.appendChild(buildSuggestions('targetGameSuggestions', GAME_SUGGESTIONS_LIST));
}

function initSuggestions () {
	switchMoveSuggestions();
}

function buildSuggestions (id, valueList) {
	const pkmnSuggestions = document.createElement('datalist');
	pkmnSuggestions.id = id;
	buildSuggestionOptions(pkmnSuggestions, valueList);
	return pkmnSuggestions;
}

function buildSuggestionOptions (datalist, valueList) {
	clearDatalist(datalist);
	for (let i = 0; i < valueList.length; i++) {
		let optionName = '';
		let optionValue = '';
		if (Array.isArray(valueList[i])) {
			optionName = valueList[i][0];
			optionValue = valueList[i][1];
		} else {
			optionName = valueList[i];
			optionValue = valueList[i];
		}
		const optionsTag = document.createElement('option');
		optionsTag.value = optionValue;
		const optionsText = document.createTextNode(optionName);
		optionsTag.appendChild(optionsText);
		datalist.appendChild(optionsTag);
	}
}

function clearDatalist (datalist) {
	while (datalist.lastChild) {
		datalist.removeChild(datalist.lastChild);
	}
}

function switchMoveSuggestions (event) {
	console.log('switching moves');
	const pkmnName = pkmnTextInputBox.value;
	const learnsetData = MOVE_SUGGESTIONS[pkmnName];
	const currentGame = getCurrentGame();
	if (learnsetData === undefined) {
		//no pkmn name entered
		console.log('couldnt find pkmn');
		return;
	}
	const gameLearnset = learnsetData.find(function (item) {
		return item.games.includes(currentGame)
	});
	if (gameLearnset === undefined) {
		console.log('couldnt find game learnset');
		return;
	}
	const moves = gameLearnset.moves;
	buildSuggestionOptions(moveSuggestionsTag, moves);
	console.log('new moves: ' + moves);
}

function getCurrentGame () {
	return GAME_TO_SK[gameTextInputBox.value];
}