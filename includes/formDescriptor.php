<?php
$formDescriptor = [
	'gameInput' => [
		'name' => 'targetGame',
		'class' => 'HTMLTextField',
		'placeholder' => $this->msg('breedingchains-game'),
		'size' => 40,
		'validation-callback' => [ $this, 'validateGameInput' ],
		'required' => true
	],
	'pkmnInput' => [
		'name' => 'targetPkmn',
		'class' => 'HTMLTextField',
		'placeholder' => $this->msg('breedingchains-pkmn'),
		'size' => 20,
		'validation-callback' => [ $this, 'validatePkmnInput' ],
		'required' => true,
	],
	'moveInput' => [
		'name' => 'targetMove',
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

//vb level
$statuslogsCheckBox = [
	'displayStatuslogs' => [
		'type' => 'check',
		'label' => 'display status logs',
	]
];