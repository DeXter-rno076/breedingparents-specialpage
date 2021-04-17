<?php
$formDescriptor = [
	'pkmnInput' => [
		'name' => 'targetPkmn',
		'class' => 'HTMLTextField',
		'cssclass' => 'inputField',
		'id' => 'pkmnInputField',
		'placeholder' => 'Pokémon',
		'size' => 20,
		'validation-callback' => [ $this, 'validatePkmn' ],
		'required' => true
	],
	'moveInput' => [
		'name' => 'targetMove',
		'class' => 'HTMLTextField',
		'cssclass' => 'inputField',
		'id' => 'pkmnInputField',
		'placeholder' => 'Attacke',
		'size' => 20,
		'validation-callback' => [ $this, 'validateMove' ],
		'required' => true
	],
	'genInput' => [
		'name' => 'targetGen',
		'label' => 'Generation: ',
		'class' => 'HTMLSelectField',
		'cssclass' => 'selectField',
		'id' => 'genInputField',
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