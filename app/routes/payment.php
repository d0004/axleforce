<?php


return [
	'payment/pay_bill' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/payment/pay-bill/{billId}',
		'conditions' => ['ln' => '[a-z]{2}', 'billId' => '[0-9]+'],
		'callable' => ['payment', 'pay_bill.php'],
		'type' => 'web',
    ],
	'payment/get_proforma_pdf' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/payment/pdf/proforma/{billId}',
		'conditions' => ['ln' => '[a-z]{2}', 'billId' => '[0-9]+'],
		'callable' => ['payment', 'get_proforma_pdf.php'],
		'type' => 'web',
    ],
	'payment/get_invoice_pdf' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/payment/pdf/invoice/{billId}',
		'conditions' => ['ln' => '[a-z]{2}', 'billId' => '[0-9]+'],
		'callable' => ['payment', 'get_invoice_pdf.php'],
		'type' => 'web',
    ],
	'payment/stripe_success' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/payment/stripe-success',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['payment', 'stripe_success.php'],
		'type' => 'web',
    ],
	'payment/stripe_cancel' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/payment/stripe-cancel',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['payment', 'stripe_cancel.php'],
		'type' => 'web',
	],
	

	// KLIX

	'payment/klix_success_callback' => [
		'via' => ['GET', 'POST'],
		'pattern' => '/{ln?}/payment/klix-success-callback/{billId}',
		'conditions' => ['ln' => '[a-z]{2}', 'billId' => '[0-9]+'],
		'callable' => ['payment', 'klix_success_callback.php'],
		'type' => 'web',
    ],
	'payment/klix_success_redirect' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/payment/klix-success-redirect/{billId}',
		'conditions' => ['ln' => '[a-z]{2}', 'billId' => '[0-9]+'],
		'callable' => ['payment', 'klix_success_redirect.php'],
		'type' => 'web',
    ],
	'payment/klix_failure_redirect' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/payment/klix-failure-redirect',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['payment', 'klix_failure_redirect.php'],
		'type' => 'web',
    ],

	// KLIX

	'payment/revolut_webhook' => [
		'via' => ['GET', 'POST'],
		'pattern' => '/{ln?}/payment/revolut-webhook',
		'conditions' => ['ln' => '[a-z]{2}'],
		'callable' => ['payment', 'revolut_webhook.php'],
		'type' => 'web',
    ],
	'payment/revolut_success_redirect' => [
		'via' => 'GET',
		'pattern' => '/{ln?}/payment/revolut-success-redirect/{billId}',
		'conditions' => ['ln' => '[a-z]{2}', 'billId' => '[0-9]+'],
		'callable' => ['payment', 'revolut_success_redirect.php'],
		'type' => 'web',
    ],
	// 'payment/klix_failure_redirect' => [
	// 	'via' => 'GET',
	// 	'pattern' => '/{ln?}/payment/klix-failure-redirect',
	// 	'conditions' => ['ln' => '[a-z]{2}'],
	// 	'callable' => ['payment', 'klix_failure_redirect.php'],
	// 	'type' => 'web',
    // ],


	
	'ajax/payment/stripe_make_payment' => [
		'via' => 'POST',
		'pattern' => '/ajax/payment/stripe_make_payment',
		'callable' => ['payment', 'i_stripe_make_payment'],
		'type' => 'ajax',
    ],
	'ajax/payment/klix_make_payment' => [
		'via' => 'POST',
		'pattern' => '/ajax/payment/klix_make_payment',
		'callable' => ['payment', 'i_klix_make_payment'],
		'type' => 'ajax',
    ],
	'ajax/payment/revolut_make_payment' => [
		'via' => 'POST',
		'pattern' => '/ajax/payment/revolut_make_payment',
		'callable' => ['payment', 'i_revolut_make_payment'],
		'type' => 'ajax',
    ],



];