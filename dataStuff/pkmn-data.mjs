import { Bot } from '/home/pg/Code/JS/backend/mediawiki-bot/index.mjs';
//todo turn bot into a public bot npm module and import that

import fs from 'fs';

//!what to remember:
//gen 7 tutor moves of evoli and pikachu
//eF-eM

//todo special forms
//todo make sure that game differences in a gen don't cause problems

//todo check for problems with name changes
//todo check all moves for templates

const GEN = 6;

//adding whitespace about doubles the file length
const FILE_SIZE_CAP = 300000;
let dataObj = {};

const bot = new Bot({
	username: 'DeXtron',
	password: 'thisshouldntbeneeded',
	url: 'https://www.pokewiki.de/api.php',
	noLogs: true
});

class PkmnObj {
	constructor (name, id, eggGroup1) {
		this.name = name.trim();

		if (isNaN(id)) {
			throw 'error in id: ' + id;
		}
		this.id = id;
		this.eggGroup1 = eggGroup1.trim();
	}

	setEggGroup2 (eggGroup) {
		//skip empty parameter value
		if (eggGroup.trim() === '') {
			return;
		}
		this.eggGroup2 = eggGroup.trim();
	}

	setGender (gender) {
		this.gender = gender;
	}

	addLearnset (type, move) {
		let learnsetType = '';
		switch (type) {
			case 'Level':
				learnsetType = 'levelLearnsets';
				break;
			case 'TMTP':
			case 'TMVM':
				learnsetType = 'tmtrLearnsets';
				break;
			case 'Zucht':
				learnsetType = 'breedingLearnsets';
				break;
			case 'Lehrer':
				learnsetType = 'tutorLearnsets';
				break;
			case 'Event':
				learnsetType = 'eventLearnsets';
				break;
		}
		if (this[learnsetType] === undefined) {
			this[learnsetType] = [];
		}
		//skip multiple occurances of a move
		if (this[learnsetType].includes(move)) {
			return;
		}
		this[learnsetType].push(move);
	}
}

(async () => {
	const pkmnList = await bot.getCatMembers('Kategorie:Pokémon');

	let skip = true;
	let pageIndex = 1;
	for (let pkmn of pkmnList) {
		if (pkmn === 'Aalabyss') {
			skip = false;
		}
		if (skip) {
			continue;
		}

		await handlePkmn(pkmn);

		console.log(pkmn + ' done');
		
		//split the data into multiple files 
		//in order to prevent db errors caused by too large strings
		if (JSON.stringify(dataObj).length > FILE_SIZE_CAP) {
			dataObj.continue = 'dasMussHierStehen';
			fs.writeFileSync('dataStuff/pkmnDataGen' + GEN + '_' + 
				pageIndex + '.json', JSON.stringify(dataObj));
			console.log('file ' + pageIndex + ' saved');
			pageIndex++;
			dataObj = {};
		}
	}

	fs.writeFileSync('dataStuff/pkmnDataGen' + GEN + 
		'_' + pageIndex + '.json', JSON.stringify(dataObj));
})();

async function handlePkmn (pkmn) {
	const mainPageTemplates = await bot.getTemplates(pkmn);

	const pkmnInfobox = mainPageTemplates.find((item) => {
		return item.title === 'Infobox Pokémon';
	});

	if (pkmnInfobox === undefined) {
		console.log('no infobox found for: ' + pkmn);
		return;
	}

	const pkmnObj = new PkmnObj(
		pkmn,
		Number(pkmnInfobox.Nr.text),
		pkmnInfobox['Ei-Gruppe'].text
	);

	setGender(pkmnObj, pkmnInfobox);

	if (pkmnInfobox['Ei-Gruppe2'] !== undefined) {
		pkmnObj.setEggGroup2(pkmnInfobox['Ei-Gruppe2'].text);
	}

	let available = await handleLearnsets(pkmn, pkmnObj);
	if (!available) {
		return;
	}

	dataObj[pkmn] = pkmnObj;
}

function setGender (pkmnObj, pkmnInfobox) {
	const genderInfo = pkmnInfobox.Geschlecht.text;

	if (genderInfo.includes('Unbekannt')) {
		pkmnObj.setGender('unknown');
		return;
	}

	if (!/100\s*%/.test(genderInfo)) {
		pkmnObj.setGender('both');
		return;
	}

	const onlyGender = /.*100\s*%\s*(\S)/.exec(genderInfo)['1'];
	if (onlyGender === '♀') {
		pkmnObj.setGender('female');
	} else if (onlyGender === '♂') {
		pkmnObj.setGender('male');
	} else {
		console.warn('unknown symbol ' + onlyGender + 'in gender info of ' + pkmnObj.name);
		pkmnObj.setGender('ooops, a problem occured :(');
	}
}

/* not needed anymore (was used when the pkmn objects were stored in an array)
function addPkmnSorted (pkmnObj) {
	if (dataArr.length === 0) {
		dataArr.push(pkmnObj);
		return;
	}

	const pkmnId = Number(pkmnObj.id);
	for (let i = 0; i < dataArr.length; i++) {
		if (pkmnId < Number(dataArr[i].id)) {
			dataArr.splice(i, 0, pkmnObj);
			return;
		}	
	}

	dataArr.push(pkmnObj);
} */

async function handleLearnsets (pkmn, pkmnObj) {
	const learnsets = await bot.getTemplates(pkmn + '/Attacken');
	const targetedLearnsets = learnsets.filter(item => {
		return item.title === 'Atk-Table' && item.g.text == GEN;
		//the == is intended (item.g.text is a string, GEN is a Number)
	});
	//handle pkmn that don't exist in the targeted gen
	if (targetedLearnsets.length === 0) {
		return false;
	}

	handleBreedingLearnset(pkmnObj, targetedLearnsets);
	handleLevelLearnset(pkmnObj, targetedLearnsets);
	handleTMTRLearnset(pkmnObj, targetedLearnsets);
	handleTutorLearnset(pkmnObj, targetedLearnsets);
	handleEventLearnset(pkmnObj, targetedLearnsets);

	return true;
}

function handleBreedingLearnset (pkmnObj, learnsets) {
	handleLearnset(pkmnObj, learnsets, 'Zucht');
}

function handleLevelLearnset (pkmnObj, learnsets) {
	handleLearnset(pkmnObj, learnsets, 'Level');
}

function handleTMTRLearnset (pkmnObj, learnsets) {
	if (GEN >= 8) handleLearnset(pkmnObj, learnsets, 'TMTP');
	else handleLearnset(pkmnObj, learnsets, 'TMVM');
}

function handleTutorLearnset (pkmnObj, learnsets) {
	handleLearnset(pkmnObj, learnsets, 'Lehrer');
}

function handleEventLearnset (pkmnObj, learnsets) {
	handleLearnset(pkmnObj, learnsets, 'Event');
}

function handleLearnset (pkmnObj, learnsets, learnsetType) {
	const tables = learnsets.filter(item => {
		return item.Art.text === learnsetType;
	});

	if (tables.length === 0) {
		return;
	}

	for (let table of tables) {
		handleLearnsetTable(table, pkmnObj, learnsetType);
		if (GEN === 7) {
			//don't mix SoMoUSUM with LGPE
			break;
		}
	}
}

function handleLearnsetTable (template, pkmnObj, learnsetType) {
	const atkRows = template['1'].templates;

	for (let row of atkRows) {
		const moveName = row['2'].text;
		if (moveName.includes('{')) {
			console.warn('{ in name of ' + moveName + ' in ' + 
				learnsetType + ' moves of ' + pkmnObj.name + ' detected');
		}
		pkmnObj.addLearnset(learnsetType, moveName);
	}
}