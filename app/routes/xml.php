<?php

return [
    'xml/salidzini' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/xml/salidzini',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['xml', 'salidzini.php'],
		'type' => 'web',
	],
    'xml/kurpirkt' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/xml/kurpirkt',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['xml', 'kurpirkt.php'],
		'type' => 'web',
	],
    'xml/sitemap' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/sitemap',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['xml', 'sitemap.php'],
		'type' => 'web',
	],
];