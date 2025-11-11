<?php

include_once('../_main.php');
include_once('./_config.php');

switch ($a) {
    
    case "i_add_to_cart":

        if(!isset($request->post['itemId']) || !isset($request->post['qty'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $productCart = new \products\ProductCart;
        if($productCart->addToCart($request)){
            $response->setAjaxOutput(['success' => true]);
            break;
        } 

        $response->setAjaxOutput(['success' => false, 'error' => 2]);

    break;

    case "i_get_small_cart":
        
        $productCart = new \products\ProductCart;
        if($html = $productCart->getSmallCart($request)){
            $response->setAjaxOutput(['success' => true, 'html' => $html, 'itemCount' => $productCart->itemCount]);
            break;
        } 

        $response->setAjaxOutput(['success' => false, 'error' => 1]);

    break;
    
    case "i_get_small_cart_dropdown":

        $productCart = new \products\ProductCart;
        if($html = $productCart->getCartDropdown($request)){
            $response->setAjaxOutput(['success' => true, 'html' => $html]);
            break;
        } 

        $response->setAjaxOutput(['success' => false, 'error' => 1]);

    break;
    
    case "i_remove_from_cart":

        if(!isset($request->post['itemId'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $productCart = new \products\ProductCart;
        if($productCart->removeFromCart($request)){
            $response->setAjaxOutput(['success' => true]);
            break;
        } 

        $response->setAjaxOutput(['success' => false, 'error' => 2]);

    break;

    case "i_product_quick_view":

        if(!isset($request->post['itemId'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $productView = new \products\view\ProductView;

        if($html = $productView->getQuickView($request->post['itemId'])){
            $response->setAjaxOutput(['success' => true, 'html' => $html]);
            break;
        }

        $response->setAjaxOutput(['success' => false, 'error' => 2]);

    break;

    case "i_load_cart_page_info":

        $productCart = new \products\ProductCart;
        $tpl->define(['cart_page' => '/products/tpl/cart_page.html']);
        $tpl->split_template('cart_page', 'CART_PAGE');

        $productIds = [];

        foreach($productCart->cartProducts as $product){
            $tpl->assign_array($product);

            $productIds[$product['ITEM_ID']] = $product['QTY'];

            $imageClass = new \products\ProductImages;
            $image = $imageClass->getMainImage($product['ITEM_ID'], 3);
            $tpl->assign("CART_IMAGE_LINK", $image);

            $tpl->parse("AJAX_TABLE_ROWS", ".full_cart_product_line");
        }

        $tpl->parse("AJAX_RESULT", "ajax_table_rows");
        $htmlTable = $tpl->fetch("AJAX_RESULT");

        
        $tpl->assign_array([
            "CART_SUBTOTAL" => $productCart->totalPrice,
            "SHIPPING_PRICE" => 0.00,
            "TAX_AMOUNT" => 0.00,
            "CART_TOTAL" => $productCart->totalPrice,
        ]);

        $tpl->parse("AJAX_RESULT_TOTAL", "ajax_card_total");
        $htmlTotal = $tpl->fetch("AJAX_RESULT_TOTAL");

        $productClass = new \products\Product;
        $data = $productClass->getProductFullInfo($productIds);

        $response->setAjaxOutput([
            'success' => true, 
            'htmlTable' => $htmlTable,
            'htmlTotal' => $htmlTotal,
            'jsonData' => $data,
        ]);

    break;

    case "i_search":

        if(!isset($request->post['query'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        if(strlen($request->post['query']) < 3){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $tpl->define(['search_header' => '/products/tpl/search_header.html']);
        $tpl->split_template('search_header', 'SEARCH_HEADER');

        $productClass = new \products\Product;

        $search = '%' . $request->post['query'] . '%';

        $productIds = [];

        $result = $db->query("SELECT DISTINCT(ITEM_ID) 
        FROM shop_products 
        INNER JOIN shop_products_lang USING (ITEM_ID) 
        INNER JOIN shop_products_prices USING (ITEM_ID)
        WHERE (TITLE LIKE ? OR SKU LIKE ? OR NEW_SKU LIKE ?) AND LANG = ? AND STATUS = 2 AND DELETED = 0
        LIMIT 15", $search, $search, $search, $lang)->fetchAll();

        foreach($result as $row){
            if(!in_array($row['ITEM_ID'], $productIds))
                $productIds[] = $row['ITEM_ID'];
        }

        $result = $db->query("SELECT DISTINCT(ITEM_ID) 
        FROM shop_products_sku 
        INNER JOIN shop_products USING (ITEM_ID)
        INNER JOIN shop_products_prices USING (ITEM_ID)
        WHERE shop_products_sku.SKU LIKE ? AND STATUS = 2 LIMIT 11", $search)->fetchAll();

        foreach($result as $row){
            if(!in_array($row['ITEM_ID'], $productIds))
                $productIds[] = $row['ITEM_ID'];
        }

        $result = $db->query("SELECT DISTINCT(ITEM_ID) 
        FROM shop_products_analogs
        INNER JOIN shop_products USING (ITEM_ID)
        INNER JOIN shop_products_prices USING (ITEM_ID)
        WHERE shop_products_analogs.ANALOG_CODE LIKE ? AND STATUS = 2 LIMIT 11", $search)->fetchAll();

        foreach($result as $row){
            if(!in_array($row['ITEM_ID'], $productIds))
                $productIds[] = $row['ITEM_ID'];
        }

        $products = $db->query("SELECT * 
        FROM shop_products 
        INNER JOIN shop_products_lang USING (ITEM_ID)
        INNER JOIN shop_products_prices USING (ITEM_ID)
        WHERE shop_products.ITEM_ID IN (" . implode(', ', array_values($productIds)) . ") AND STATUS = 2 AND LANG = ? LIMIT 11", $lang)->fetchAll();

        if(count($products)){
            foreach($products as $product){
                $tpl->assign_array($product);
                $tpl->assign("SEARCH_PRODUCT_PRICE", $productClass->getProductPrice($product['ITEM_ID']));

                $imageClass = new \products\ProductImages;
                $image = $imageClass->getMainImage($product['ITEM_ID'], 3);

                $tpl->assign("SEARCH_ITEM_IMAGE", $image);

                $tpl->parse("SEARCH_RESULT_PRODUCT", ".search_result_product");
            }
        } else {
            $tpl->parse("SEARCH_RESULT_PRODUCT", "nothing_found");
        }

        // $categoryClass = new \category\Category;

        // $categories = $db->query("SELECT * FROM shop_categories_lang WHERE TITLE LIKE ? AND LANG = ? LIMIT 5", '%' . $request->post['query'] . '%', $lang)->fetchAll();
        // if(count($categories)){
        //     foreach($categories as $category){
        //         $tpl->assign_array($category);
        //         $tpl->assign("URL_SLUG", $categoryClass->getUrl($category['CATEGORY_ID'])); 
        //         $tpl->parse("SEARCH_RESULT_CATEGORY", ".search_result_category");
        //     }
        // } else {
        //     $tpl->parse("SEARCH_RESULT_CATEGORY", "nothing_found");
        // }


        $tpl->parse("AJAX_RESULT", "search_header");
        $html = $tpl->fetch("AJAX_RESULT");

        $response->setAjaxOutput([
            'success' => true, 
            'html' => $html,
        ]);

    break;

    case "i_create_order":

        // echo '<pre>' . print_r($request->post, 2) . '</pre>';
        // die;

        $required = ['name', 'surname', 'country', 'email', 'phone', 'checkout_payment_method', 'shippingCountry', 'checkout_delivery'];
        foreach($required as $field){
            if(!isset($request->post[$field])){
                $response->setAjaxOutput(['success' => false, 'error' => 1]);
                break 2;
            }
        }

        if(!$user->isLegal){
            if(!isset($request->post['address'])){
                $response->setAjaxOutput(['success' => false, 'error' => 1]);
                break;
            }
        }

        $productCart = new \products\ProductCart;
        if($productCart->totalPrice <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        if(!array_key_exists($request->post['country'], $countries)){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);
            break;
        }
        if(!array_key_exists($request->post['shippingCountry'], $countries)){
            $response->setAjaxOutput(['success' => false, 'error' => 4]);
            break;
        }

        $deliveryClass = \delivery\type\Factory::getClass($request->post['checkout_delivery']);
        if(!$deliveryClass instanceof \delivery\type\AbstractType){
            $response->setAjaxOutput(['success' => false, 'error' => 441]);
            break;
        }

        if(!$deliveryClass->setCountry($request->post['shippingCountry'])){
            $response->setAjaxOutput(['success' => false, 'error' => 442]);
            break;
        }

        if(!$deliveryClass->setPrice()){
            $response->setAjaxOutput(['success' => false, 'error' => 443]);
            break;
        }

        if(!$deliveryClass->checkShow()){
            $response->setAjaxOutput(['success' => false, 'error' => 444]);
            break;
        }

        $orderData = [];
        $orderData['UID'] = $user->uid;
        $orderData['NAME'] = $request->post['name'];
        $orderData['SURNAME'] = $request->post['surname'];

        if($user->isLegal){
            $orderData['COMPANY_VAT'] = $user->userLegal['VAT_NUMBER'];
            $orderData['COMPANY_NAME'] = $user->userLegal['COMPANY_NAME'];
            $orderData['COMPANY_ADDRESS'] = $user->userLegal['COMPANY_ADDRESS'];
        } else {
            $orderData['ADDRESS'] = $request->post['address'];
        }

        $orderData['COUNTRY'] = $request->post['country'];
        $orderData['SHIPPING_COUNTRY'] = $request->post['shippingCountry'];
        
        $orderTotalPrice = $productCart->totalPrice;

        // CALCULATE DELIVERY PRICE
        $deliveryPrice = $deliveryClass->getPrice();
        // CALCULATE DELIVERY PRICE
        
        
        $orderData['AMOUNT'] = $orderTotalPrice;

        $orderData['DELIVERY_TYPE'] = $request->post['checkout_delivery'];

        $orderData['EMAIL'] = $request->post['email'];
        $orderData['PHONE'] = $request->post['phone'];
        $orderData['NOTES'] = $request->post['notes'];
        
        
        $db->lockTables(['tbl_order_product_lock', 'tbl_order', 'tbl_order_detail', 'shop_products']);

        // foreach($productCart->cartProducts as $product){
        //     $reservedCount = $db->query("SELECT SUM(QTY) as QTY FROM tbl_order_product_lock WHERE ITEM_ID = ?", $product['ITEM_ID'])->fetchArray();
        //     $reservedCount = $reservedCount['QTY'] ?: 0;

        //     $count = $db->query("SELECT STOCK FROM shop_products WHERE ITEM_ID = ?", $product['ITEM_ID'])->fetchArray();
        //     $count = $count['STOCK'] ?: 0;

        //     // $available = $count - $reservedCount;
        //     // if($available < $product['QTY']){
        //     //     $db->unlockTables();
        //     //     $response->setAjaxOutput(['success' => false, 'error' => 41]);
        //     //     break 2;
        //     // }
        // }
        

        $columns = implode("`, `",array_keys($orderData));
        $values  = implode("', '", array_values($orderData));

        $db->query("INSERT INTO tbl_order (`$columns`) VALUES ('$values')");
        $orderId = $db->lastInsertID();

        if(!$orderId){
            $db->unlockTables();
            $response->setAjaxOutput(['success' => false, 'error' => 5]);
            break;
        }

        // $tax = number_format($orderTotalPrice * \payment\Tax::getTax($request->post['shippingCountry']), 2, '.', '');
        $tax = 0.00;

        foreach($productCart->cartProducts as $product){
            $detailData = [];
            $detailData['ORDER_ID'] = $orderId;
            $detailData['ITEM_ID'] = $product['ITEM_ID'];
            $detailData['UID'] = $user->uid;
            $detailData['AMOUNT'] = $product['PRICE'];
            $detailData['QTY'] = $product['QTY'];
            $detailData['TOTAL_AMOUNT'] = $product['TOTAL_PRICE'];
            $tmpTax = $product['TOTAL_PRICE'] * \payment\Tax::getTax($request->post['shippingCountry']);
            $detailData['VAT'] = $tmpTax;
            $tax += $tmpTax;
            $columns = implode("`, `",array_keys($detailData));
            $values  = implode("', '", array_values($detailData));

            $db->query("INSERT INTO tbl_order_detail (`$columns`) VALUES ('$values')");
            if($db->affectedRows() <= 0){
                $db->unlockTables();
                $response->setAjaxOutput(['success' => false, 'error' => 33]);
                break 2;
            }

            $db->query("INSERT INTO tbl_order_product_lock (ORDER_ID, ITEM_ID, QTY, CREATE_DATE) VALUES (?, ?, ?, now())", $orderId, $product['ITEM_ID'], $product['QTY']);
            if($db->affectedRows() <= 0){
                $db->unlockTables();
                $response->setAjaxOutput(['success' => false, 'error' => 42]);
                break 2;
            }
        }

        $db->unlockTables();

        $deliveryDetailData = $request->post['checkout_delivery_detail'][$deliveryClass->getType()];
        $deliveryData = $deliveryClass->prepareData($deliveryDetailData);

        $db->query("INSERT INTO tbl_order_delivery (ORDER_ID, DELIVERY_TYPE, DELIVERY_DATA) VALUES (?, ?, ?)", 
        $orderId, $deliveryClass->getType(), json_encode($deliveryData, JSON_UNESCAPED_UNICODE));

        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 34]);
            break;
        }

        $billId = $money->createBill($user->uid, 'shop_payment', $request->post['checkout_payment_method']);
        if(!$billId){
            $response->setAjaxOutput(['success' => false, 'error' => 6]);
            break;
        }

        if(!$money->insertBillDetail($billId, $orderTotalPrice, ['orderId' => $orderId], 'products')){
            $response->setAjaxOutput(['success' => false, 'error' => 7]);
            break;
        }

        $deliveryTax = number_format($deliveryPrice * \payment\Tax::getTax($request->post['shippingCountry']), 2, '.', '');

        if(!$money->insertBillDetail($billId, $deliveryPrice, ['country' => $request->post['shippingCountry'], 'tax' => $deliveryTax], 'delivery')){
            $response->setAjaxOutput(['success' => false, 'error' => 9]);
            break;
        }

        $tax += $deliveryTax;

        if(!$money->insertBillDetail($billId, $tax, ['country' => $request->post['shippingCountry'], 'percent' => \payment\Tax::getTax($request->post['shippingCountry'])], 'tax')){
            $response->setAjaxOutput(['success' => false, 'error' => 8]);
            break;
        }


        $db->query("INSERT INTO tbl_bill_to_order (BILL_ID, ORDER_ID) VALUES (?, ?)", $billId, $orderId);

        $productCart->clearCart();

        $telegram = new \_class\TelegramBot;
        $telegram->sendMessage("🎊 Создан новый заказ на сайте axleforce\n\nНомер заказа: $orderId\nСумма заказа: $orderTotalPrice €");

        $response->setAjaxOutput([
            'success' => true, 
            'billId' => $billId,
            'paymentUrl' => $tpl->urlFor('payment/pay_bill', ['billId' => $billId]),
        ]);

    break;

    case "i_calculate_vat":

        if(!$request->post['country'] || !$request->post['amount']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        if(!array_key_exists($request->post['country'], $countries)){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $percent = \payment\Tax::getTax($request->post['country']);
        
        if($percent){
            $tax = number_format($request->post['amount'] * $percent, 2, '.', '');
        } else {
            $tax = 0.00;
        }

        $response->setAjaxOutput(['success' => true, 'tax' => $tax]);
        
    break;

    case "i_load_delivery_options":

        if(!$request->post['country']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        if(!array_key_exists($request->post['country'], $countries)){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $html = '';
        $deliveryObj = [];

        $deliveryObj[] = new \delivery\type\Omniva;
        $deliveryObj[] = new \delivery\type\CircleK;
        $deliveryObj[] = new \delivery\type\TakeInOffice;
        $deliveryObj[] = new \delivery\type\LatvijasPasts;
        // if($user->hasStatus(40)){
            
        // }

        $first = true;
        foreach($deliveryObj as $delivery){
            if($delivery->setCountry($request->post['country'])){
                if($delivery->setPrice()){
                    if($delivery->checkShow()){
                        $html .= $delivery->getForm($first);
                        $first = false;
                    }
                }
            }
        }

        $response->setAjaxOutput(['success' => true, 'html' => $html]);

    break;

    case "i_calculate_delivery":

        if(!$request->post['country'] || !$request->post['amount'] || !$request->post['deliveryType']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }


        $deliveryClass = \delivery\type\Factory::getClass($request->post['deliveryType']);
        if(!($deliveryClass instanceof \delivery\type\AbstractType)){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);
            break;
        }

        if(!$deliveryClass->setCountry($request->post['country'])){
            $response->setAjaxOutput(['success' => false, 'error' => 4]);
            break;
        }

        if(!$deliveryClass->setPrice()){
            $response->setAjaxOutput(['success' => false, 'error' => 5]);
            break;
        }

        if(!$deliveryClass->checkShow()){
            $response->setAjaxOutput(['success' => false, 'error' => 6]);
            break;
        }

        $price = $deliveryClass->getPrice();

        $response->setAjaxOutput(['success' => true, 'price' => $price]);

    break;

    case "i_change_payment_method":

        if(!$request->post['billId'] || !$request->post['method']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        if(!$money->changePaymentMethod($request->post['billId'], $request->post['method'])){
            $response->setAjaxOutput(['success' => false, 'error' => 2 . '-' . $money->getError()]);
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_get_item_json":

        if(!$request->post['itemIds']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }
        
        $productClass = new \products\Product;
        $data = $productClass->getProductFullInfo($request->post['itemIds']);

        $response->setAjaxOutput(['success' => true, 'data' => $data]);

    break;
}



$response->output();
exit;