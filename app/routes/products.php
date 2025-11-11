<?php

return [
	'products/single_product' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/product/{slug}',
		'conditions' => ['ln' => '[a-z]{2}', 'slug' => '[a-zA-Z0-9-_*–]+'],
		'callable' => ['products', 'single_product.php'],
		'type' => 'web',
	],
	'products/cart' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/cart',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['products', 'cart_page.php'],
		'type' => 'web',
	],
	'products/checkout' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/cart/checkout',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['products', 'checkout.php'],
		'type' => 'web',
	],
	

	'ajax/products/add_to_cart' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/add_to_cart',
		'callable' => ['products', 'i_add_to_cart'],
		'type' => 'ajax',
    ],
	'ajax/products/get_small_cart' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/get_small_cart',
		'callable' => ['products', 'i_get_small_cart'],
		'type' => 'ajax',
    ],
	'ajax/products/get_small_cart_dropdown' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/get_small_cart_dropdown',
		'callable' => ['products', 'i_get_small_cart_dropdown'],
		'type' => 'ajax',
    ],
	'ajax/products/remove_from_cart' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/remove_from_cart',
		'callable' => ['products', 'i_remove_from_cart'],
		'type' => 'ajax',
    ],
	'ajax/products/product_quick_view' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/product_quick_view',
		'callable' => ['products', 'i_product_quick_view'],
		'type' => 'ajax',
    ],
	'ajax/products/load_cart_page_info' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/load_cart_page_info',
		'callable' => ['products', 'i_load_cart_page_info'],
		'type' => 'ajax',
    ],
	'ajax/products/search' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/search',
		'callable' => ['products', 'i_search'],
		'type' => 'ajax',
	],
	'ajax/products/create_order' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/create_order',
		'callable' => ['products', 'i_create_order'],
		'type' => 'ajax',
    ],
	'ajax/products/calculate_vat' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/calculate_vat',
		'callable' => ['products', 'i_calculate_vat'],
		'type' => 'ajax',
    ],
	'ajax/products/load_delivery_options' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/load_delivery_options',
		'callable' => ['products', 'i_load_delivery_options'],
		'type' => 'ajax',
    ],
	'ajax/products/calculate_delivery' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/calculate_delivery',
		'callable' => ['products', 'i_calculate_delivery'],
		'type' => 'ajax',
    ],
	'ajax/products/change_payment_method' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/change_payment_method',
		'callable' => ['products', 'i_change_payment_method'],
		'type' => 'ajax',
    ],
	'ajax/products/get_item_json' => [
		'via' => 'POST',
		'pattern' => '/ajax/products/get_item_json',
		'callable' => ['products', 'i_get_item_json'],
		'type' => 'ajax',
    ],
];