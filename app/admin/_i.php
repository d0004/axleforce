<?php

include_once('../_main.php');
include_once('./_config.php');

use \Gumlet\ImageResize;

switch ($a) {
    
    case "i_load_file_langs":

        if(!$request->post['path']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $content = @file_get_contents($request->post['path']);
        if(!$content){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $matches = $wordProcessing->_get_all_matches($content);
        // echo '<pre>'; print_r($matches); echo '</pre>'; die;

        $tpl->define(['translations' => '/admin/tpl/translations.html']);
        $tpl->split_template('translations', 'TRANSLATIONS');

        $tpl->assign("FILE_PATH", $request->post['path']);

        $usedIds = [];

        foreach($matches[0] as $i => $translation){
            $tpl->clear_parse("LANG_TRANSLATION");
            if(!in_array($matches[1][$i], $usedIds)){

                foreach(['lv', 'ru', 'en'] as $transLang){

                    $result = $db->query("SELECT * FROM tbl_words WHERE ID = ? AND LID = ?", $matches[1][$i], $transLang)->fetchArray();

                    $tpl->assign_array([
                        "LANG_ID" => $matches[1][$i],
                        "HTML_TEXT" => $matches[3][$i],
                        "TRANS_LANG" => $transLang,
                        "TRANSLATION_VALUE" => $result['VALUE'],
                    ]);
                    $tpl->parse("LANG_TRANSLATION", ".lang_translation");
                }
                $tpl->parse("LANG_ROW", ".lang_row");
                $usedIds[] = $matches[1][$i];
            }
        }

        $tpl->parse("AJAX_RESULT", "ajax_result");

        $response->setAjaxOutput(['success' => true, 'html' => $tpl->fetch("AJAX_RESULT")]);

    break;

    case "i_save_lang":

        if(!$request->post['langId'] || !$request->post['file']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $parts = explode("/", $request->post['file']);
        
        $module = '';
        foreach($parts as $i => $part){
            $module = $part;
            if(isset($parts[$i-1]) && $parts[$i-1] == "app"){
                break;
            }
        }

        $file = basename($request->post['file']);

        foreach(['lv', 'ru', 'en'] as $transLang){
            // var_dump(1);
            $translation = $request->post['translation'][$transLang] ? $request->post['translation'][$transLang] : '';
            $result = $db->query("SELECT * FROM tbl_words WHERE ID = ? AND LID = ?", $request->post['langId'], $transLang)->fetchArray();
            // echo '<pre>'; print_r($result); echo '</pre>';
            if($result){
                $db->query("UPDATE tbl_words SET `VALUE` = ? WHERE ID = ? AND LID = ?", $translation, $request->post['langId'], $transLang);
                // if($db->affectedRows() <= 0){
                //     $response->setAjaxOutput(['success' => false, 'error' => 2]);
                //     break 2;
                // }
            } else {
                $db->query("INSERT INTO tbl_words (MODULE, FILE, ID, LID, `VALUE`) VALUES (?, ?, ?, ?, ?)", $module, $file, $request->post['langId'], $transLang, $translation);
                if($db->affectedRows() <= 0){
                    $response->setAjaxOutput(['success' => false, 'error' => 3]);
                    break 2;
                }
            }
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_upload_image_by_sku":

        if(!$request->post['sku']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $product = $db->query("SELECT * FROM shop_products WHERE SKU = ? OR NEW_SKU = ?", $request->post['sku'], $request->post['sku'])->fetchArray();
        if(!$product){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        // ini_set('memory_limit', '1000M');
        // ini_set('max_execution_time', '300');

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PUBLIC_DIR . '/files_public/product_images_original'));
        $files = array(); 
        foreach ($rii as $file) {
            if ($file->isDir()){ 
                continue;
            }
            $files[] = $file->getPathname(); 
        }

        $fileArr = [];

        foreach($files as $file){
            $pathParts = explode("/", $file);
            $pathParts = array_reverse($pathParts);
            $sku = str_replace(" ", "", $pathParts['1']);
            if($sku != $request->post['sku']){
                continue;
            }

            $fileArr[] = $file;
        }

        if($fileArr){
            $db->query("DELETE FROM shop_products_files WHERE ITEM_ID = ?", $product['ITEM_ID']);
        }

        foreach($fileArr as $file){

            $fileId = 0;
            $result = $db->query("SELECT MAX(FILE_ID) AS FILE_ID FROM shop_products_files")->fetchArray();
            if($result){
                $fileId = $result['FILE_ID'] + 1;
            }

            $directory = dirname($file);
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            $pathParts = explode("/", $file);
            $pathParts = array_reverse($pathParts);

            $newDirectory = str_replace("product_images_original", "product_images", $directory);
            if(!is_dir($newDirectory)){
                if (!@mkdir($newDirectory, 0777, true)) {
                    continue;
                }
            }

            $publicPath = [];
            foreach($pathParts as $i => $part){
                if($i == 0) continue;
                if($part == "server") break;
                if($part == "product_images_original") $part = "product_images";
                $publicPath[] = $part;
            }

            $publicPath = array_reverse($publicPath);
            $publicPath = '/' . implode("/", $publicPath);
            
            $fileName = str_replace(" ", "", basename($file, "." . $ext));


            $imageResizer = new \products\ImageResizer;

            $name = $fileName . '-0.' . $ext;
            $newFileName = $newDirectory . '/' . $name;
            $originalInfo = getimagesize($file);

            if($imageResizer->resizeWithFill($file, $newFileName, $originalInfo['0'], $originalInfo['1'], 85)){
                $db->query("INSERT INTO shop_products_files (FILE_ID, ITEM_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, 0)", $fileId, $product['ITEM_ID'], $publicPath . '/' . $name);
            }

            foreach($imageSizes as $size => $data){
                $name = $fileName . '-' . $size . '.' . $ext;
                $newFileName = $newDirectory . '/' . $name;
                if($imageResizer->resizeWithFill($file, $newFileName, $data['w'], $data['h'], 85)){
                    $db->query("INSERT INTO shop_products_files (FILE_ID, ITEM_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, ?)", $fileId, $product['ITEM_ID'], $publicPath . '/' . $name, $size);
                }
            }
        }


        $db->query("INSERT INTO tmp_upload_image_success (SKU) VALUES (?)", $request->post['sku']);
        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_upload_image_by_file":

        if(!$request->post['file'] | !$request->post['itemId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $file = $request->post['file'];
        if(!file_exists($file)){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $imageResizer = new \products\ImageResizer;

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $fileName = str_replace(" ", "", basename($file, "." . $ext));

        $publicPath = '/files_public/product_images/' . $request->post['itemId'];
        
        $newPath = PUBLIC_DIR . '/files_public/product_images/' . $request->post['itemId'];
        if(!is_dir($newPath)){
            if (!@mkdir($newPath, 0777, true)) {
                $response->setAjaxOutput(['success' => false, 'error' => 3]);
                break;
            }
        }

        $name = $fileName . '-0.' . $ext;
        $newFileName = $newPath . '/' . $name;
        
        $originalInfo = getimagesize($file);


        $fileId = 0;
        $result = $db->query("SELECT MAX(FILE_ID) AS FILE_ID FROM shop_products_files")->fetchArray();
        if($result){
            $fileId = $result['FILE_ID'] + 1;
        }

        if($imageResizer->resizeWithFill($file, $newFileName, $originalInfo['0'], $originalInfo['1'], 85)){
            $db->query("INSERT INTO shop_products_files (FILE_ID, ITEM_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, 0)", $fileId, $request->post['itemId'], $publicPath . '/' . $name);
        }

        foreach($imageSizes as $size => $data){
            $name = $fileName . '-' . $size . '.' . $ext;
            $newFileName = $newPath . '/' . $name;
            if($imageResizer->resizeWithFill($file, $newFileName, $data['w'], $data['h'], 85)){
                $db->query("INSERT INTO shop_products_files (FILE_ID, ITEM_ID, FILE_TYPE, `FILE`, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, ?)", $fileId, $request->post['itemId'], $publicPath . '/' . $name, $size);
            }
        }

        $db->query("INSERT INTO tmp_upload_image_file_success (SKU) VALUES (?)", $request->post['file']);

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_save_bank_data":

        if(!$request->files['file']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $filePrivate = new \_class\FilesPrivate(FILE_PRIVATE_PATH);
        $csvFileName = $filePrivate->saveFileTmp('file', $request->files);

        if(!$csvFileName){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $path = $filePrivate->getTmpPath($csvFileName);
        if(!$path){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);
            break;
        }

        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl('|');
        foreach ($file as $row) {
            print_r($row);
        }
        
        unlink($path);
        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_manual_upload_bank_operation":

        if(!$request->post['id'] || !$request->post['amount'] || !$request->post['date'] || !$request->post['detail']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $db->query("INSERT IGNORE INTO bank_operations (ID, AMOUNT, DATE, DETAIL, COMMENT) VALUES (?, ?, ?, ?, ?)",
            $request->post['id'], $request->post['amount'], $request->post['date'], $request->post['detail'], $request->post['comment']
        );

        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_load_operations":

        $tpl->define(['invoices_and_bank_operations' => '/admin/tpl/invoices_and_bank_operations.html']);
        $tpl->split_template('invoices_and_bank_operations', 'INVOICES_AND_BANK_OPERATIONS');

        if($request->post['showAllOperations'] == "true"){
            $result = $db->query("SELECT * FROM bank_operations ORDER BY DATE ASC")->fetchAll();    
        } else {
            $result = $db->query("SELECT * FROM bank_operations WHERE STATUS = 0 ORDER BY DATE ASC")->fetchAll();
        }
        
        foreach($result as $row){
            $tpl->assign("COLOR", $operationStatusColors[$row['STATUS']]);
            $tpl->assign_array($row);
            $tpl->parse("AJAX_RESULT", ".operation_row");
        }

        if($tpl->get_assigned("AJAX_RESULT")){
            $html = $tpl->fetch("AJAX_RESULT");
        } else {
            $tpl->parse("AJAX_RESULT", ".not_found");
            $html = $tpl->fetch("AJAX_RESULT");
        }

        $response->setAjaxOutput(['success' => true, 'html' => $html]);

    break;

    case "i_search_bill":

        if(!$request->post['billId'] || !$request->post['id']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $tpl->define(['invoices_and_bank_operations' => '/admin/tpl/invoices_and_bank_operations.html']);
        $tpl->split_template('invoices_and_bank_operations', 'INVOICES_AND_BANK_OPERATIONS');

        $billId = preg_replace("/[^0-9]/", "", $request->post['billId']);

        $bill = $money->getBill($billId);
        $operation = $money->getOperation($request->post['id']);
        if(!$bill || !$operation){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        foreach($bill['detail'] as $detail){
            switch($detail['ITEM']){
                case "products":
                    $tpl->assign("PRODUCTS_PRICE", $detail['AMOUNT']);
                    break;
                case "delivery":
                    $tpl->assign("DELIVERY_PRICE", $detail['AMOUNT']);
                    break;
                case "tax":
                    $tpl->assign("TAX_AMOUNT", $detail['AMOUNT']);
                    break;
            }
        }

        $orderId = $db->query("SELECT * FROM tbl_bill_to_order WHERE BILL_ID = ?", $bill['BILL_ID'])->fetchArray();
        $orderId = $orderId['ORDER_ID'];
        if(!$orderId){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $order = $db->query("SELECT * FROM tbl_order WHERE ORDER_ID = ?", $orderId)->fetchArray();
        $tpl->assign("ORDER_JSON", json_encode($order));

        $result = $db->query("SELECT * FROM tbl_order_delivery WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchArray();
        $deliveryClass = \delivery\type\Factory::getClass($result['DELIVERY_TYPE']);
        if($deliveryClass instanceof \delivery\type\AbstractType){
            $tpl->assign_array([
                "DELIVERY_JSON" => json_encode(['type' => $deliveryClass->getTitle(), 'address' => $deliveryClass->getAddress($result['DELIVERY_DATA'])]),
            ]);
        }

        $result = $db->query("SELECT * 
        FROM tbl_order_detail 
        INNER JOIN shop_products USING (ITEM_ID)
        WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchAll();
    
        $products = [];
        foreach($result as $row){
            $products[] = [
                'SKU' => $row['NEW_SKU'],
                'QTY' => $row['QTY'],
                'total amount' => $row['TOTAL_AMOUNT'],
                'vat' => $row['VAT'],
            ];
        }
    
        $tpl->assign("PRODUCT_JSON", json_encode($products));

        $user = $db->query("SELECT * FROM tbl_user WHERE UID = ?", $bill['UID'])->fetchArray();

        $tpl->assign_array([
            "BILL_ID" => $bill['BILL_ID'],
            "BILL_UID" => $bill['UID'],
            "CREATE_DATE" => $bill['CREATE_DATE'],
            "EXPIRE_DATE" => $bill['EXPIRE_DATE'],
            "AMOUNT" => $bill['AMOUNT'],
            "OPERATION_AMOUNT" => $operation['AMOUNT'],
            "BILL_STATUS" => $bill['STATUS'],
            "VALID_AMOUNT" => $bill['AMOUNT'] == $operation['AMOUNT'] ? true : false,

            "OPERATION_STATUS" => $operation['STATUS'],
            "ID" => $operation['ID'],

            "FNAME" => $user['FNAME'],
            "LNAME" => $user['LNAME'],
            "IS_COMPANY" => $user['IS_COMPANY'] ? true : false,
        ]);

        $tpl->parse("AJAX_RESULT", "bill_info");
        $html = $tpl->fetch("AJAX_RESULT");
        
        $response->setAjaxOutput(['success' => true, 'html' => $html]);

    break;

    case "i_process_bank_operation":

        if(!$request->post['billId'] || !$request->post['id']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $bill = $money->getBill($request->post['billId']);
        $operation = $money->getOperation($request->post['id']);
        if(!$bill || !$operation){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        if(($operation['AMOUNT'] != $bill['AMOUNT']) && $request->post['correctAmount'] == 1){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);
            break;
        }

        if(!$money->processOperation($operation['ID'], $bill['BILL_ID'])){
            $response->setAjaxOutput(['success' => false, 'error' => 4]);
            break;
        }

        $processPayment = new \payment\process_payment\Process;
        $processPayment->setLogId($logId, "BANK");
        if(!$processPayment->processPayment($bill['BILL_ID'])){
            $money->resetOperation($operation['ID']);
            $response->setAjaxOutput(['success' => false, 'error' => 5]);
            break;
        }

        $response->setAjaxOutput(['success' => true, 'url' => $tpl->urlFor('admin/success_payment', ['billId' => $bill['BILL_ID']])]);

    break;

    case "i_add_admin_order_status":

        if(!$request->post['orderId'] || !$request->post['status']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $order = $db->query("SELECT * FROM tbl_order WHERE ORDER_ID = ?", $request->post['orderId'])->fetchArray();
        if(!$order){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $result = $db->query("SELECT * FROM tbl_order_admin_status WHERE ORDER_ID = ?", $request->post['orderId'])->fetchArray();
        if($result && $result['ADMIN_STATUS'] == $request->post['status']){
            $db->query("DELETE FROM tbl_order_admin_status WHERE ORDER_ID = ?", $request->post['orderId']);
        } else {
            $db->query("INSERT INTO tbl_order_admin_status (ORDER_ID, ADMIN_STATUS) VALUES (?, ?) ON DUPLICATE KEY UPDATE ADMIN_STATUS = ?", $request->post['orderId'], $request->post['status'], $request->post['status']);    
            
            if($db->affectedRows() > 0){
                if($request->post['status'] == 3){
                    
                    $bill = $db->query("SELECT * FROM tbl_bill_to_order WHERE ORDER_ID = ?", $request->post['orderId'])->fetchArray();            
                    if($bill){                    

                        $currentDate = date('Y-m-d H:i:s');
                        $db->query("INSERT INTO tbl_order_confirmed (ORDER_ID, CREATE_DATE) VALUES (?, now()) 
                        ON DUPLICATE KEY UPDATE CREATE_DATE = ?", $request->post['orderId'], $currentDate);

                        $date = substr($currentDate, 0, -9);
                        $path = FILE_PRIVATE_PATH . 'tmp/invoice/' . $date;
                        @mkdir($path, 0777, true);
                        
                        $pdfClass = new \payment\pdf\invoice\ShopPayment;
                        // $pdfClass->setCustomDate($currentDate);
                        $pdfClass->saveTmp($path);
                        $pdfClass->getDocument($bill['BILL_ID']);
                        $path = $pdfClass->getFliePath($bill['BILL_ID']);
    
                        $email = new \email\Email;
                        $email->sendTo('order_ready_to_shipment', $order['EMAIL'], [
                            'LINK' => APP_DOMAIN . '/profile/order-history/' . $order['ORDER_ID'],
                            'ORDER_ID' => $order['ORDER_ID'],
                        ], ['Pavadzīme' => $path]); 
                    } else {
                        $email = new \email\Email;
                        $email->sendTo('order_ready_to_shipment', $order['EMAIL'], [
                            'LINK' => APP_DOMAIN . '/profile/order-history/' . $order['ORDER_ID'],
                            'ORDER_ID' => $order['ORDER_ID'],
                        ]);
                    }              
                }
            }
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_add_omniva_track_number":
        
        if(!$request->post['code'] || !$request->post['orderId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $order = $db->query("SELECT * FROM tbl_order WHERE ORDER_ID = ?", $request->post['orderId'])->fetchArray();
        if(!$order){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $db->query("INSERT INTO tbl_order_omniva_track (ORDER_ID, TRACK_CODE, CREATE_DATE) VALUES (?, ?, now())", $request->post['orderId'], $request->post['code']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $email = new \email\Email;
        $email->sendTo('omniva_tracking_code', $order['EMAIL'], [
            'CODE' => $request->post['code'],
            'ORDER_ID' => $order['ORDER_ID'],
        ]);

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_make_as_legal":

        if(!$request->post['uid'] || !$request->post['vat'] || !$request->post['companyName'] || !$request->post['companyAddress']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $result = $db->query("SELECT * FROM tbl_user WHERE UID = ? AND IS_COMPANY = 1", $request->post['uid'])->fetchArray();
        if($result){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $user->setLegalInfo($request->post['uid'], $request->post['vat'], $request->post['companyName'], $request->post['companyAddress']);

        $db->query("UPDATE tbl_user SET IS_COMPANY = 1 WHERE UID = ?", $request->post['uid']);

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_change_informer_status":
        
        if(!$request->post['id']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $db->query("UPDATE informers SET STATUS = !STATUS WHERE ID = ?", $request->post['id']);
        $response->setAjaxOutput(['success' => true]);

    break;
    
    case "i_save_informer":
        
        if(!$request->post['id'] || !$request->post['type'] || !$request->post['message']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $db->query("UPDATE informers SET MESSAGE = ?, TYPE = ? WHERE ID = ?", $request->post['message'], $request->post['type'], $request->post['id']);
        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_add_new_informer":

        if(!$request->post['lang']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $db->query("INSERT INTO informers (LANG, CREATE_DATE, STATUS, MESSAGE, TYPE) VALUES (?, now(), 0, '', '')", $request->post['lang']);
        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_get_orders":

        $tpl->define(['orders' => '/admin/tpl/orders.html']);
        $tpl->split_template('orders', 'ORDERS');

        $orders = $db->query("SELECT *, tbl_bill.CREATE_DATE AS ORDER_CREATE_DATE
        FROM tbl_bill
        INNER JOIN tbl_bill_to_order USING (BILL_ID)
        INNER JOIN tbl_user USING (UID)
        LEFT JOIN tbl_user_legal USING (UID)
        LEFT JOIN tbl_order_admin_status USING (ORDER_ID)
        LEFT JOIN tbl_order_omniva_track USING (ORDER_ID)
        LEFT JOIN tbl_order_lvp_info USING (ORDER_ID)
        WHERE STATUS = 2
        ORDER BY FIELD(ADMIN_STATUS, '2') DESC, tbl_bill.BILL_ID DESC")->fetchAll();

        $invoice = new \payment\pdf\invoice\ShopPayment;

        foreach($orders as $order){

            $orderRow = $db->query("SELECT * FROM tbl_order WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchArray();

            $tpl->assign_array([
                'ORDER_PHONE' => $orderRow['PHONE'],
                'ORDER_EMAIL' => $orderRow['EMAIL'],
            ]);

            $tpl->assign("TRACK_CODE", false);
            if($order['TRACK_CODE']){
                $tpl->assign("TRACK_CODE", $order['TRACK_CODE']);
            }

            $tpl->assign("COLOR", "");
            if($order['ADMIN_STATUS'] == 1){
                $tpl->assign("COLOR", "#ff070757");
            }

            if($order['ADMIN_STATUS'] == 2){
                $tpl->assign("COLOR", "#ffc10757");
            }

            if($order['ADMIN_STATUS'] == 3){
                $tpl->assign("COLOR", "#24ef002b");
            }

            $count = $db->query("SELECT COUNT(*) AS COU FROM tbl_order_detail WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchArray();

            $order['ORDER_CREATE_DATE'] = substr($order['ORDER_CREATE_DATE'], 0, -3);
            $tpl->assign_array($order);

            $tpl->assign("NAME_JSON", json_encode([
                'UID' => $order['UID'],
                'name' => $order['FNAME'],
                'surname' => $order['LNAME'],
                'VAT' => $order['VAT_NUMBER'],
                'company name' => $order['COMPANY_NAME'],
                'company address' => $order['COMPANY_ADDRESS'],
            ]));


            $invoicePath = $invoice->getFliePath($order['BILL_ID']);

            $tpl->assign("HAS_INVOICE", false);
            if($invoicePath && file_exists($invoicePath)){
                $tpl->assign("HAS_INVOICE", true);
            }

            $tpl->assign_array([
                "ITEM_COUNT" => $count['COU'],
            ]);

            $result = $db->query("SELECT * 
            FROM tbl_order_detail 
            INNER JOIN shop_products USING (ITEM_ID)
            WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchAll();

            $products = [];
            foreach($result as $row){
                $products[] = [
                    'SKU' => $row['NEW_SKU'],
                    'QTY' => $row['QTY'],
                    'total amount' => $row['TOTAL_AMOUNT'],
                    'vat' => $row['VAT'],
                ];
            }

            $tpl->assign("PRODUCT_JSON", json_encode($products));

            $result = $db->query("SELECT * FROM tbl_order_delivery WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchArray();

            $deliveryClass = \delivery\type\Factory::getClass($result['DELIVERY_TYPE']);
            if($deliveryClass instanceof \delivery\type\AbstractType){
                $tpl->assign_array([
                    "DELIVERY_TYPE_ORIGINAL" => $result['DELIVERY_TYPE'],
                    "DELIVERY_TYPE" => $deliveryClass->getTitle(),
                    "DELIVERY_JSON" => json_encode(['type' => $deliveryClass->getTitle(), 'address' => $deliveryClass->getAddress($result['DELIVERY_DATA'])]),
                ]);
            }

            // continue;
            

            $tpl->parse("ORDER_ROW", ".order_row");
        }

        $html = '';
        if($tpl->get_assigned("ORDER_ROW")){
            $html = $tpl->fetch("ORDER_ROW");
        }

        $response->setAjaxOutput(['success' => true, 'html' => $html]);

    break;

    case "i_restore_order":

        if(!$request->post['billId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $order = $db->query("SELECT * FROM tbl_bill_to_order WHERE BILL_ID = ?", $request->post['billId'])->fetchArray();
        if(!$order){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $result = $db->query("SELECT * FROM tbl_order_detail WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchAll();
        if(!$result){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);
            break;
        }

        $checkLocked = $db->query("SELECT * FROM tbl_order_product_lock WHERE ORDER_ID = ?", $order['ORDER_ID'])->fetchAll();
        if($checkLocked){
            $db->query("DELETE FROM tbl_order_product_lock WHERE ORDER_ID = ?", $order['ORDER_ID']);
        }

        $db->query("UPDATE tbl_bill SET STATUS = 0, EXPIRE_DATE = now() + INTERVAL 3 DAY WHERE BILL_ID = ?", $request->post['billId']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 4]);
            break;
        }

        $db->query("UPDATE tbl_order SET STATUS = 0 WHERE ORDER_ID = ?", $order['ORDER_ID']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 5]);
            break;
        }

        foreach($result as $row){
            $db->query("INSERT INTO tbl_order_product_lock (ORDER_ID, ITEM_ID, QTY, CREATE_DATE) VALUES (?, ?, ?, now())", $row['ORDER_ID'], $row['ITEM_ID'], $row['QTY']);
            if($db->affectedRows() <= 0){
                $response->setAjaxOutput(['success' => false, 'error' => 6]);
                break;
            }
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_confirm_order":

        if(!$request->post['billId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $db->query("INSERT IGNORE INTO tbl_bill_flag (BILL_ID, FLAG, VALUE) VALUES (?, 'confirmed', 1)", $request->post['billId']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_create_new_banner":

        $db->query("INSERT INTO banners (LANG, ITEM_ID, CONTENT) VALUES ('en', 0, ?)", json_encode([]));
        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_save_banner":

        if(!$request->post['bannerId'] || !$request->post['lang'] || !$request->post['itemId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $content = [];
        $content['BANNER_SUBTITLE'] = $request->post['subtitle'] ?? '';
        $content['BANNER_TITLE'] = $request->post['title'] ?? '';
        $content['BANNER_CONTENT'] = $request->post['content'] ?? '';
        $content['BANNER_BUTTON_TEXT'] = $request->post['buttonText'] ?? '';
        $content['BANNER_BUTTON_LINK'] = $request->post['buttonLink'] ?? '';

        $db->query("UPDATE banners SET 
            ITEM_ID = ?,
            LANG = ?,
            CONTENT = ?,
            IS_ACTIVE = ?
        WHERE BANNER_ID = ?
        ", 
            $request->post['itemId'] ? $request->post['itemId'] : 0, 
            $request->post['lang'],
            json_encode($content), 
            isset($request->post['active']) ? 1 : 0, 
            $request->post['bannerId']
        );
        
        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_lvp_create":

        if(!$request->post['orderId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $order = $db->query("SELECT * 
        FROM tbl_order
        INNER JOIN tbl_order_delivery USING (ORDER_ID)
        WHERE ORDER_ID = ? AND tbl_order_delivery.DELIVERY_TYPE = 'lvpasts'", $request->post['orderId'])->fetchArray();

        if(!$order){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $orderProducts = $db->query("SELECT *
        FROM tbl_order_detail 
        INNER JOIN shop_products USING (ITEM_ID)
        WHERE ORDER_ID = ?", $request->post['orderId'])->fetchAll();

        if(!$orderProducts){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);
            break;
        }

        $order['DELIVERY_DATA'] = json_decode($order['DELIVERY_DATA'], true);
        $order['PRODUCTS_IN_ORDER'] = $orderProducts;

        $delivery = new \delivery\type\LatvijasPasts;
        $parcelNumber = $delivery->create($order);

        if(!$parcelNumber){
            $response->setAjaxOutput(['success' => false, 'error' => 4]);
            break;
        }

        $db->query("REPLACE INTO tbl_order_lvp_info (ORDER_ID, LVP_PARCEL, LVP_CREATE_DATE) VALUES (?, ?, now())", $order['ORDER_ID'], $parcelNumber);
        
        $response->setAjaxOutput(['success' => true]);

    break;

    // case "i_lvp_label":

    //     if(!$request->post['parcel']){
    //         $response->setAjaxOutput(['success' => false, 'error' => 1]);
    //         break;
    //     }

    //     $delivery = new \delivery\type\LatvijasPasts;
    //     $parcelNumber = $delivery->label($request->post['parcel']);


    // break;
}

$response->output();
exit;