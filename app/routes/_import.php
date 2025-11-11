<?php

return [
	'_import/excel_import' => [
		'via' => 'GET',
		'pattern' => '/_import/excel_import',
		// 'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['_import', 'excel_import.php'],
		'type' => 'web',
	],
	'_import/excel_reader' => [
		'via' => 'GET',
		'pattern' => '/_import/excel_reader',
		// 'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['_import', 'excel_reader.php'],
		'type' => 'web',
	],
	'_import/export' => [
		'via' => 'GET',
		'pattern' => '/_import/export',
		// 'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['_import', 'export.php'],
		'type' => 'web',
	],
	
	//AJAX REQUESTS

];