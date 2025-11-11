<?php

return [
	'profile/index' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/profile',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['profile', 'index.php'],
		'type' => 'web',
	],
	'profile/edit_profile' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/profile/edit',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['profile', 'edit_profile.php'],
		'type' => 'web',
	],
	'profile/address' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/profile/address',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['profile', 'address.php'],
		'type' => 'web',
	],
	'profile/address_edit' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/profile/edit-address/{recId?}',
		'conditions' => ['ln' => '[a-z]{2}', 'recId' => '[0-9]+'],
		'callable' => ['profile', 'address_edit.php'],
		'type' => 'web',
	],
	'profile/order_history_all' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/profile/order-history',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['profile', 'order_history.php'],
		'type' => 'web',
	],
	'profile/order_history' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/profile/order-history/{orderId?}',
		'conditions' => ['ln' => '[a-z]{2}', 'orderId' => '[0-9]+'],
		'callable' => ['profile', 'order_history.php'],
		'type' => 'web',
	],
	

	'profile/logout' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/profile/log-out',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['profile', 'logout.php'],
		'type' => 'web',
	],


	'ajax/profile/edit_profile' => [
		'via' => 'POST',
		'pattern' => '/ajax/profile/edit_profile',
		'callable' => ['profile', 'i_edit_profile'],
		'type' => 'ajax',
    ],
	'ajax/profile/save_edit_address' => [
		'via' => 'POST',
		'pattern' => '/ajax/profile/save_edit_address',
		'callable' => ['profile', 'i_save_edit_address'],
		'type' => 'ajax',
    ],
	'ajax/profile/remove_address' => [
		'via' => 'POST',
		'pattern' => '/ajax/profile/remove_address',
		'callable' => ['profile', 'i_remove_address'],
		'type' => 'ajax',
    ],
];