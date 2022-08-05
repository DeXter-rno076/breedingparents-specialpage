<?php
$formDescriptor = [
	'gameInput' => [
		'class' => 'HTMLTextField',
		'placeholder' => $this->msg('breedingchains-game'),
		'size' => 40,
		'validation-callback' => [ $this, 'validateGameInput' ],
		'required' => true
	],
	'pkmnInput' => [
		'class' => 'HTMLTextField',
		'placeholder' => $this->msg('breedingchains-pkmn'),
		'size' => 20,
		'validation-callback' => [ $this, 'validatePkmnInput' ],
		'required' => true,
	],
	'moveInput' => [
		'class' => 'HTMLTextField',
		'placeholder' => $this->msg('breedingchains-move'),
		'size' => 20,
		'validation-callback' => [ $this, 'validateMoveInput' ],
		'required' => true
	]
];

//sb level
$debuglogsCheckBox = [
	'displayDebuglogs' => [
		'type' => 'check',
		'label' => 'display debug logs',
	]
];

$statuslogsCheckBox = [
	'displayStatuslogs' => [
		'type' => 'check',
		'label' => 'display status logs',
	]
];

$detailedSuccessorFilterLogsCheckBox = [
    'createDetailedSuccessorFilterLogs' => [
        'type' => 'check',
        'label' => 'create detailed successor filter logs'
    ]
];