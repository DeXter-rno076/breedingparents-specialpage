<?php

$iconFile = null;
$iconFileLink = '';
try {
	$iconFile = VisualNode::getIcon('Pokémon-Icon 150.png');
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
				'id' => 'breedingChainsOldGenMarkerExample',
				'class' => 'breedingChainsSVGExample',
				'xmlns' => 'http://www.w3.org/2000/svg',
				'width' => 56,
				'height' => 56
			], [
				new HTMLElement('a',[
					'href' => 'Mewtu/Attacken#8._Generation'
				], [
					new HTMLElement('circle', [
						'cx' => 28,
						'cy' => 28,
						'r' => 26
					]),
					new HTMLElement('image', [
						'x' => 12,
						'y' => 8,
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
				'width' => 56,
				'height' => 56
			], [
				new HTMLElement('a',[
					'href' => 'Mewtu/Attacken#8._Generation'
				], [
					new HTMLElement('circle', [
						'cx' => 28,
						'cy' => 28,
						'r' => 26
					]),
					new HTMLElement('image', [
						'x' => 12,
						'y' => 8,
						'width' => 32,
						'height' => 42,
						'xlink:href' => $iconFileLink
					])
				])
			]),
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