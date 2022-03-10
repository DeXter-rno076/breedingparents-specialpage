<?php

$iconFile = null;
$iconFileLink = '';
try {
	$iconFile = FrontendPkmn::getPkmnIcon('150');
} catch (FileNotFoundException $e) {
	$errorMsg = new ErrorMessage($e);
	$errorMsg->output();
}

if (!is_null($iconFile)) {
	$iconFileLink = $iconFile->getUrl();
}

$markerExamplesTable = new HTMLElement('table', [
	'id' => 'breedingChainsExplanationTable'
], [
	new HTMLElement('th',['colspan' => 2], [Constants::i18nMsg('breedingchains-markerexplanation-head')]),
	new HTMLElement('tr', [], [
		new HTMLElement('td', [], [
			new HTMLElement('svg', [
				'id' => 'breedingChainsEventMarkerExample',
				'class' => 'breedingChainsSVGExample',
				'xmlns' => 'http://www.w3.org/2000/svg',
				'width' => 50,
				'height' => 50
			], [
				new HTMLElement('a',[
					'href' => 'Mewtu/Attacken#8. Generation'
				], [
					new HTMLElement('circle', [
						'cx' => 25,
						'cy' => 25,
						'r' => 24
					]),
					new HTMLElement('image', [
						'x' => 10,
						'y' => 5,
						'width' => 32,
						'height' => 42,
						'xlink:href' => $iconFileLink
					])
				])
			]),
		]),
		new HTMLElement('td', [], [
			Constants::i18nMsg('breedingchains-markerexplanation-oldgen')
		])
	]),
	new HTMLElement('tr', [], [
		new HTMLElement('td', [], [
			new HTMLElement('svg', [
				'id' => 'breedingChainsEventMarkerExample',
				'class' => 'breedingChainsSVGExample',
				'xmlns' => 'http://www.w3.org/2000/svg',
				'width' => 50,
				'height' => 56
			], [
				new HTMLElement('a', [
					'href' => 'Mewtu/Attacken#8. Generation'
				], [
					new HTMLElement('rect', [
						'x' => 2,
						'y' => 2,
						'width' => 46,
						'height' => 52,
						'rx' => 6,
						'ry' => 6
					]),
					new HTMLElement('image', [
						'x' => 7,
						'y' => 7,
						'width' => 32,
						'height' => 42,
						'xlink:href' => $iconFileLink
					])
				])
			])
		]),
		new HTMLElement('td', [], [
			Constants::i18nMsg('breedingchains-markerexplanation-event')
		])
	]),
	new HTMLElement('tr', [], [
		new HTMLElement('td', [
			'colspan' => 2,
			'id' => 'breedingChainsMarkerExplanationsBottomNote'
		], [
			Constants::i18nMsg('breedingchains-markerexplanation-bottomnote')
		])
	])
]);