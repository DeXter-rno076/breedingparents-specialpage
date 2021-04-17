import fs from 'fs';

const GEN = 7;
let pkmnData = JSON.parse(fs.readFileSync('dataStuff/pkmnDataGen'
	+ GEN + '_1.json', {encoding: 'utf8'}));

const eggGroupData = {};

let indexCounter = 2;

//assembling multiple pkmnData files into one object
while (pkmnData.continue !== undefined) {
	delete pkmnData.continue;
	let additionalPkmnData = JSON.parse(
		fs.readFileSync('dataStuff/pkmnDataGen' + GEN + '_' + indexCounter + '.json')
	);
	Object.assign(pkmnData, additionalPkmnData);
	indexCounter++;
}

for (let pkmn in pkmnData) {
	const eggGroup1 = pkmnData[pkmn].eggGroup1;
	const eggGroup2 = pkmnData[pkmn].eggGroup2 || null;

	handleEggGroup(eggGroup1, pkmnData[pkmn]);
	handleEggGroup(eggGroup2, pkmnData[pkmn]);
}

fs.writeFileSync(
	'dataStuff/eggGroupDataGen' + GEN + '.json',
	JSON.stringify(eggGroupData)
);

function handleEggGroup (eggGroup, pkmn) {
	if (eggGroup === null) {
		return;
	}

	if (eggGroupData[eggGroup] === undefined) {
		eggGroupData[eggGroup] = [];
	}

	const targetedEggGroupArr = eggGroupData[eggGroup];
	insertSorted(targetedEggGroupArr, pkmn);
}

function insertSorted (eggGroupArr, newEggGroupMember) {
	let targetIndex = eggGroupArr.length;

	for (let i = 0; i < eggGroupArr.length; i++) {
		const curLookedAtPkmnName = eggGroupArr[i];
		const curLookedAtPkmnId = pkmnData[curLookedAtPkmnName].id;

		if (newEggGroupMember.id < curLookedAtPkmnId) {
			targetIndex = i;
			break;
		}
	}

	eggGroupArr.splice(targetIndex, 0, newEggGroupMember.name);
}