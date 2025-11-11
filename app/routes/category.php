<?php

return [
	'category/index' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/shop/{slug}',
		'conditions' => ['ln' => '[a-z]{2}', 'slug' => '[a-zA-Z0-9-_\/]+'],
		'callable' => ['category', 'index.php'],
		'type' => 'web',
	],
	


	'ajax/category/get_category_products' => [
		'via' => 'POST',
		'pattern' => '/ajax/category/get_category_products',
		'callable' => ['category', 'i_get_category_products'],
		'type' => 'ajax',
    ],
	
];