//!doesnt work in firefox mobile for some reasons
const pkmnInputContainer = document.getElementById('mw-input-wppkmnInput');
const pkmnTextInputBox = pkmnInputContainer.firstChild;
const moveInputContainer = document.getElementById('mw-input-wpmoveInput');
const moveTextInputBox = moveInputContainer.firstChild;
const gameInputContainer = document.getElementById('mw-input-wpgameInput');
const gameTextInputBox = gameInputContainer.firstChild;
const moveSuggestionsTag = buildSuggestions('targetMoveSuggestions', []);

pkmnTextInputBox.addEventListener('change', switchMoveSuggestions);
gameTextInputBox.addEventListener('change', switchMoveSuggestions);

const pkmnSuggestions = mw.config.get('breedingchains-pkmnSuggestions');
const moveSuggestions = mw.config.get('breedingchains-moveSuggestions');
const gameToSk = mw.config.get('breedingchains-gameToSk');
const gameSuggestions = Object.keys(gameToSk);

initDatalists();
initSuggestions();

function initDatalists () {
	pkmnTextInputBox.setAttribute('list', 'targetPkmnSuggestions');
	pkmnInputContainer.appendChild(buildSuggestions('targetPkmnSuggestions', pkmnSuggestions));

	moveTextInputBox.setAttribute('list', 'targetMoveSuggestions');
	moveInputContainer.appendChild(moveSuggestionsTag);

	gameTextInputBox.setAttribute('list', 'targetGameSuggestions');
	gameInputContainer.appendChild(buildSuggestions('targetGameSuggestions', gameSuggestions));
}

function initSuggestions () {
	switchMoveSuggestions();
}

function buildSuggestions (id, valueList) {
	const suggestionsTag = document.createElement('datalist');
	suggestionsTag.id = id;
	buildSuggestionOptions(suggestionsTag, valueList);
	return suggestionsTag;
}

function buildSuggestionOptions (datalistTag, valueList) {
	clearDatalist(datalistTag);
	for (let i = 0; i < valueList.length; i++) {
		const optionsTag = document.createElement('option');
		optionsTag.value = valueList[i];
		const optionsText = document.createTextNode(valueList[i]);
		optionsTag.appendChild(optionsText);
		datalistTag.appendChild(optionsTag);
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
	const learnsetData = moveSuggestions[pkmnName];
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
	return gameToSk[gameTextInputBox.value];
}