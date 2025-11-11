<?php

return [
	'index' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['home', 'index.php'],
		'type' => 'web',
	],
	'about' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/about',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['home', 'about.php'],
		'type' => 'web',
	],
	'contacts' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/contacts',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['home', 'contacts.php'],
		'type' => 'web',
	],
	
	'home/login_page' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/login',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['home', 'login_page.php'],
		'type' => 'web',
	],
	'home/search' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/search/{query?}',
		'conditions' => ['ln' => '[a-z]{2}', 'query' => '.*'],
		'callable' => ['home', 'search.php'],
		'type' => 'web',
	],
	'home/privacy_policy' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/privacy-policy',
		'conditions' => ['ln' => '[a-z]{2}', 'query' => '.*'],
		'callable' => ['home', 'privacy_policy.php'],
		'type' => 'web',
	],
	'home/purchase_terms' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/purchase-terms',
		'conditions' => ['ln' => '[a-z]{2}', 'query' => '.*'],
		'callable' => ['home', 'purchase_terms.php'],
		'type' => 'web',
	],
	'home/vacancy' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/vacancy',
		'conditions' => ['ln' => '[a-z]{2}', 'query' => '.*'],
		'callable' => ['home', 'vacancy.php'],
		'type' => 'web',
	],

	//AJAX REQUESTS

	'ajax/home/chack_vat_number' => [
		'via' => 'POST',
		'pattern' => '/ajax/home/chack_vat_number',
		'callable' => ['home', 'i_chack_vat_number'],
		'type' => 'ajax',
    ],
	'ajax/home/subscribe' => [
		'via' => 'POST',
		'pattern' => '/ajax/home/subscribe',
		'callable' => ['home', 'i_subscribe'],
		'type' => 'ajax',
    ],
	'ajax/home/check_email' => [
		'via' => 'POST',
		'pattern' => '/ajax/home/check_email',
		'callable' => ['home', 'i_check_email'],
		'type' => 'ajax',
    ],
	'ajax/home/contact_us' => [
		'via' => 'POST',
		'pattern' => '/ajax/home/contact_us',
		'callable' => ['home', 'i_contact_us'],
		'type' => 'ajax',
    ],
	
];