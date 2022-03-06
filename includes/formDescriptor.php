<?php
$formDescriptor = [
	'pkmnInput' => [
		'name' => 'targetPkmn',
		'class' => 'HTMLTextField',
		'placeholder' => $this->msg('breedingchains-pkmn'),
		'size' => 20,
		'validation-callback' => [ $this, 'validatePkmnInput' ],
		'required' => true
	],
	'moveInput' => [
		'name' => 'targetMove',
		'class' => 'HTMLTextField',
		'placeholder' => $this->msg('breedingchains-move'),
		'size' => 20,
		'validation-callback' => [ $this, 'validateMoveInput' ],
		'required' => true
	],
	'genInput' => [
		'name' => 'targetGen',
		'label' => $this->msg('breedingchains-gen'),
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
		'validation-callback' => [ $this, 'validateGenInput' ]
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