<?php

return [
	// 'login' => [
	// 	'via' => 'GET',
	// 	'pattern' => '/{ln?}/login',
	// 	'conditions' => ['ln' => '[a-z]{2}'],
	// 	'callable' => ['login_register', 'login.php'],
	// 	'type' => 'web',
    // ],
	'login_register/forgot_pass' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/forgot-pass',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['login_register', 'forgot_pass.php'],
		'type' => 'web',
    ],
	'login_register/reset_password' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/reset-password/{code}',
		'conditions' => ['ln' => '[a-z]{2}', 'code' => '[a-zA-Z0-9]{32}'],
		'callable' => ['login_register', 'reset_password.php'],
		'type' => 'web',
    ],
	'login_register/admin_go' => [
		'via' => ['GET', 'POST'],
		'pattern' => '/{ln?}/login-register/admin-go/{uid?}',
		'conditions' => ['ln' => '[a-z]{2}', 'uid' => '[0-9]{9}'],
		'callable' => ['login_register', 'admin_go.php'],
		'type' => 'web',
    ],
	'login_register/verify_email' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/verify-email/{code}',
		'conditions' => ['ln' => '[a-z]{2}', 'code' => '[a-zA-Z0-9]+'],
		'callable' => ['login_register', 'verify_email.php'],
		'type' => 'web',
    ],
	'login_register/unsubscribe' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/email/unsubscribe/{email}',
		'conditions' => ['ln' => '[a-z]{2}', 'email' => '[a-zA-Z0-9\.@-]+'],
		'callable' => ['login_register', 'unsubscribe.php'],
		'type' => 'web',
    ],
	'login_register/unsubscribe_ok' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/email/unsubscribe-ok/{email}',
		'conditions' => ['ln' => '[a-z]{2}', 'email' => '[a-zA-Z0-9\.@-]+'],
		'callable' => ['login_register', 'unsubscribe_ok.php'],
		'type' => 'web',
    ],

    //AJAX REQUESTS

    'ajax/register/register' => [
		'via' => 'POST',
		'pattern' => '/ajax/register/register',
		'callable' => ['login_register', 'i_register'],
		'type' => 'ajax',
    ],
    'ajax/register/register_legal' => [
		'via' => 'POST',
		'pattern' => '/ajax/register/register_legal',
		'callable' => ['login_register', 'i_register_legal'],
		'type' => 'ajax',
    ],
    'ajax/login/login' => [
		'via' => 'POST',
		'pattern' => '/ajax/login/login',
		'callable' => ['login_register', 'i_login'],
		'type' => 'ajax',
	],
    'ajax/login_register/request_password_reset' => [
		'via' => 'POST',
		'pattern' => '/ajax/login_register/request_password_reset',
		'callable' => ['login_register', 'i_request_password_reset'],
		'type' => 'ajax',
	],
    'ajax/login_register/password_reset' => [
		'via' => 'POST',
		'pattern' => '/ajax/login_register/password_reset',
		'callable' => ['login_register', 'i_password_reset'],
		'type' => 'ajax',
	],
    'ajax/login_register/resend_validation_code' => [
		'via' => 'POST',
		'pattern' => '/ajax/login_register/resend_validation_code',
		'callable' => ['login_register', 'i_resend_validation_code'],
		'type' => 'ajax',
	],
];