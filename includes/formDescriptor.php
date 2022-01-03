<?php
$formDescriptor = [
	'pkmnInput' => [
		'name' => 'targetPkmn',
		'class' => 'HTMLTextField',
		'placeholder' => 'Pokémon',
		'size' => 20,
		'validation-callback' => [ $this, 'validatePkmn' ],
		'required' => true
	],
	'moveInput' => [
		'name' => 'targetMove',
		'class' => 'HTMLTextField',
		'placeholder' => 'Attacke',
		'size' => 20,
		'validation-callback' => [ $this, 'validateMove' ],
		'required' => true
	],
	'genInput' => [
		'name' => 'targetGen',
		'label' => 'Generation: ',
		'class' => 'HTMLSelectField',
		'options' => [//breeding was implented in gen 2
			'8' => 8,
			'7' => 7,
			'6' => 6,
			'5' => 5,
			'4' => 4,
			'3' => 3,
			'2' => 2,
		],
		'validation-callback' => [ $this, 'validateGen' ]
	]
];

//sb level
$debuglogsCheckBox = [
	'displayDebuglogs' => [
		'type' => 'check',
		'label' => 'display debug logs',
	]
];

//vb level
$statuslogsCheckBox = [
	'displayStatuslogs' => [
		'type' => 'check',
		'label' => 'display status logs',
	]
];