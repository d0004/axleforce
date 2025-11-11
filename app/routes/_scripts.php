<?php

return [
	'_scripts/slug_generator' => [
		'via' => 'GET',
		'pattern' => '/_scripts/slug_generator',
		'callable' => ['_scripts', 'slug_generator.php'],
		'type' => 'web',
	],
	
	'_scripts/import_images_test' => [
		'via' => 'GET',
		'pattern' => '/_scripts/import_images_test',
		'callable' => ['_scripts', 'import_images_test.php'],
		'type' => 'web',
	],
	'_scripts/prices_and_weight' => [
		'via' => 'GET',
		'pattern' => '/_scripts/prices_and_weight',
		'callable' => ['_scripts', 'prices_and_weight.php'],
		'type' => 'web',
	],
	'_scripts/update_stock' => [
		'via' => 'GET',
		'pattern' => '/_scripts/update_stock',
		'callable' => ['_scripts', 'update_stock.php'],
		'type' => 'web',
	],
	'_scripts/test_email' => [
		'via' => 'GET',
		'pattern' => '/_scripts/test_email',
		'callable' => ['_scripts', 'test_email.php'],
		'type' => 'web',
	],
];