<?php

include_once('../_main.php');
include_once('./_config.php');

switch ($a) {

    case "i_get_category_list":

        $id = $request->post['id'];
        if($id == '#'){
            $id = 0;
        }

        $result = $db->query("SELECT * FROM shop_categories INNER JOIN shop_categories_lang USING (CATEGORY_ID) WHERE PARENT_ID = ? AND LANG = 'lv' AND DELETED = 0", $id)->fetchAll();
        $data = [];
        foreach($result as $row){
            $color = $row['STATUS'] == 2 ? 'green' : 'red';
            $data[] = [
                "id" => $row['CATEGORY_ID'],
                "text" => "<span style='color: $color'>" . $row['CATEGORY_ID'] . ' - ' . $row['TITLE'] . '</span>',
                "textPlain" => $row['CATEGORY_ID'] . ' - ' . $row['TITLE'],
                "state" => "closed",
                "children" => true,
            ];
        }

        $response->setAjaxOutput($data);

    break;

    case "i_get_category_short_info":

        $id = $request->post['id'];
        if(!$id){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $result = $db->query("SELECT * FROM shop_categories INNER JOIN shop_categories_lang USING (CATEGORY_ID) WHERE CATEGORY_ID = ? AND LANG = 'lv'", $id)->fetchArray();
        if(!$result){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        
        $tpl->define(['index' => '/admin_products/tpl/index.html']);
        $tpl->split_template('index', 'INDEX');

        $tpl->assign_array($result);
        $tpl->parse("AJAX_RESULT", "short_info");
        
        $response->setAjaxOutput(['success' => true, 'html' => $tpl->fetch("AJAX_RESULT")]);

    break;

    case "i_create_subcategory":

        if(!isset($request->post['parentId'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $db->query("INSERT INTO shop_categories (PARENT_ID) VALUES (?)", $request->post['parentId']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $categoryId = $db->lastInsertID();
        if(!$categoryId){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);    
            break;
        }

        $db->query("INSERT INTO shop_categories_lang (CATEGORY_ID, LANG, TITLE) VALUES (?, 'lv', ?)", $categoryId, 'New category');
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 4]);    
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_get_category_actions":

        $id = $request->post['id'];
        if(!$id){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $result = $db->query("SELECT * FROM shop_categories WHERE CATEGORY_ID = ?", $id)->fetchArray();
        if(!$result){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        
        $tpl->define(['index' => '/admin_products/tpl/index.html']);
        $tpl->split_template('index', 'INDEX');
        // var_dump($result); die;
        $tpl->assign_array($result);
        $tpl->parse("AJAX_RESULT", "category_actions");
        
        $response->setAjaxOutput(['success' => true, 'html' => $tpl->fetch("AJAX_RESULT")]);

    break;

    case "i_get_category_by_lang":

        if(!isset($request->post['categoryId']) || !isset($request->post['lang'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $result = $db->query("SELECT * FROM shop_categories LEFT JOIN shop_categories_lang USING (CATEGORY_ID) WHERE CATEGORY_ID = ? AND LANG = ?", $request->post['categoryId'], $request->post['lang'])->fetchArray();
        
        $tpl->define(['edit_category' => '/admin_products/tpl/edit_category.html']);
        $tpl->split_template('edit_category', 'EDIT_CATEGORY');
        
        $tpl->assign_array($result);
        $tpl->assign_array([
            "LANGUAGE" => $request->post['lang'],
            "CATEGORY_ID" => $request->post['categoryId'],
        ]);

        $tpl->parse("AJAX_RESULT", "category_info");
    
        $response->setAjaxOutput(['success' => true, 'html' => $tpl->fetch("AJAX_RESULT")]);

    break;

    case "i_save_category":
        
        if(!isset($request->post['categoryId']) || !isset($request->post['lang'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $result = $db->query("SELECT * FROM shop_categories_lang WHERE CATEGORY_ID = ? AND LANG = ?",
            $request->post['categoryId'], 
            $request->post['lang']
        )->fetchArray();

        if($result){
            $db->query("UPDATE shop_categories_lang SET TITLE = ?, DESCRIPTION = ?, SLUG = ? WHERE CATEGORY_ID = ? AND LANG = ?",
                isset($request->post['title']) ? $request->post['title'] : '', 
                isset($request->post['description']) ? $request->post['description'] : '', 
                isset($request->post['slug']) ? str_replace(" ", "-", strtolower($request->post['slug'])) : '', 
                $request->post['categoryId'], 
                $request->post['lang']
            );
            
            if($db->affectedRows() <= 0){
                $response->setAjaxOutput(['success' => false, 'error' => 2]);    
                break;
            }
        } else {
            $db->query("INSERT INTO shop_categories_lang (TITLE, DESCRIPTION, CATEGORY_ID, LANG) VALUES (?, ?, ?, ?)",
                isset($request->post['title']) ? $request->post['title'] : '', 
                isset($request->post['description']) ? $request->post['description'] : '', 
                $request->post['categoryId'], 
                $request->post['lang']
            );
            if($db->affectedRows() <= 0){
                $response->setAjaxOutput(['success' => false, 'error' => 3]);    
                break;
            }
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_get_product_by_lang":

        if(!isset($request->post['itemId']) || !isset($request->post['lang'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $check = $db->query("SELECT * FROM shop_products_lang WHERE ITEM_ID = ? AND LANG = ?", $request->post['itemId'], $request->post['lang'])->fetchArray();
        if(!$check){
            $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, DESCR) VALUES (?, ?, '')", $request->post['itemId'], $request->post['lang']);
        }

        $result = $db->query("SELECT * 
        FROM shop_products 
        LEFT JOIN shop_products_lang USING (ITEM_ID) 
        LEFT JOIN shop_products_prices USING (ITEM_ID)
        WHERE ITEM_ID = ? AND LANG = ?", $request->post['itemId'], $request->post['lang'])->fetchArray();
        
        if(!$result){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $tpl->define(['edit_product' => '/admin_products/tpl/edit_product.html']);
        $tpl->split_template('edit_product', 'EDIT_PRODUCT');
        
        // var_dump($result); die;

        $tpl->assign_array($result);
        $tpl->assign_array([
            "LANGUAGE" => $request->post['lang'],
            "ITEM_ID" => $request->post['itemId'],
        ]);

        $tpl->parse("AJAX_RESULT", "product_info");

        $response->setAjaxOutput(['success' => true, 'html' => $tpl->fetch("AJAX_RESULT")]);

    break;

    case "i_save_product":
        
        if(!isset($request->post['itemId']) || !isset($request->post['lang'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $product = $db->query("SELECT * FROM shop_products WHERE ITEM_ID = ?",
            $request->post['itemId']
        )->fetchArray();

        if(!$product){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $result = $db->query("SELECT * FROM shop_products_lang WHERE ITEM_ID = ? AND LANG = ?",
            $request->post['itemId'], 
            $request->post['lang']
        )->fetchArray();

        if($result){
            $db->query("UPDATE shop_products_lang SET TITLE = ?, SHORT_DESCR = ?, DESCR = ? WHERE ITEM_ID = ? AND LANG = ?",
                isset($request->post['title']) ? $request->post['title'] : '', 
                isset($request->post['shortDescr']) ? $request->post['shortDescr'] : '', 
                isset($request->post['descr']) ? $request->post['descr'] : '', 
                $request->post['itemId'], 
                $request->post['lang']
            );
            
            // if($db->affectedRows() <= 0){
            //     $response->setAjaxOutput(['success' => false, 'error' => 3]);    
            //     break;
            // }
        } else {
            $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, ?, ?, ?, ?)",
                $request->post['itemId'], 
                $request->post['lang'],
                isset($request->post['title']) ? $request->post['title'] : '', 
                isset($request->post['shortDescr']) ? $request->post['shortDescr'] : '', 
                isset($request->post['descr']) ? $request->post['descr'] : ''
            );

            if($db->affectedRows() <= 0){
                $response->setAjaxOutput(['success' => false, 'error' => 4]);    
                break;
            }
        }

        if(isset($request->post['sku']) && $request->post['sku'] != $product['SKU']){
            $db->query("UPDATE IGNORE shop_products SET SKU = ? WHERE ITEM_ID = ?",
                isset($request->post['sku']) ? $request->post['sku'] : '',
                $request->post['itemId']
            );
            if($db->affectedRows() <= 0){
                $response->setAjaxOutput(['success' => false, 'error' => 50]);    
                break;
            }
        }

        if(isset($request->post['stock']) && $request->post['stock'] != $product['STOCK']){
            $db->query("UPDATE IGNORE shop_products SET STOCK = ? WHERE ITEM_ID = ?",
                isset($request->post['stock']) ? $request->post['stock'] : '0',
                $request->post['itemId']
            );
            if($db->affectedRows() <= 0){
                $response->setAjaxOutput(['success' => false, 'error' => 51]);    
                break;
            }
        }

        if(isset($request->post['weight']) && $request->post['weight'] != $product['WEIGHT']){
            $db->query("UPDATE IGNORE shop_products SET WEIGHT = ? WHERE ITEM_ID = ?",
                isset($request->post['weight']) ? $request->post['weight'] : '0',
                $request->post['itemId']
            );
            if($db->affectedRows() <= 0){
                $response->setAjaxOutput(['success' => false, 'error' => 52]);    
                break;
            }
        }

        $dimensions = ['width', 'height', 'length'];
        foreach($dimensions as $param){
            if(isset($request->post[$param]) && $request->post[$param] != $product[strtoupper($param)]){
                $db->query("UPDATE IGNORE shop_products SET " . strtoupper($param) . " = ? WHERE ITEM_ID = ?",
                    $request->post[$param],
                    $request->post['itemId']
                );
            }
        }

        if(isset($request->post['newSku']) && $request->post['newSku'] != $product['NEW_SKU']){
            $db->query("UPDATE IGNORE shop_products SET NEW_SKU = ? WHERE ITEM_ID = ?",
                isset($request->post['newSku']) ? $request->post['newSku'] : '',
                $request->post['itemId']
            );
            if($db->affectedRows() <= 0){
                $response->setAjaxOutput(['success' => false, 'error' => 53]);    
                break;
            }
        }

        if(isset($request->post['price']) && $request->post['price'] != ''){
            $db->query("INSERT INTO shop_products_prices (ITEM_ID, STANDART_PRICE, STANDART_PRICE_WITH_VAT, DISCOUNT_PRICE, DISCOUNT_PRICE_WITH_VAT) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE STANDART_PRICE = ?, STANDART_PRICE_WITH_VAT = ?, DISCOUNT_PRICE = ?, DISCOUNT_PRICE_WITH_VAT = ?",
                $request->post['itemId'],
                (isset($request->post['price']) && $request->post['price'] != '') ? $request->post['price'] : 0.00,
                (isset($request->post['price']) && $request->post['price'] != '') ? $request->post['price'] * 1.21 : 0.00,

                (isset($request->post['discountPrice']) && $request->post['discountPrice'] != '') ? $request->post['discountPrice'] : 0.00,
                (isset($request->post['discountPrice']) && $request->post['discountPrice'] != '') ? $request->post['discountPrice'] * 1.21 : 0.00,

                (isset($request->post['price']) && $request->post['price'] != '') ? $request->post['price'] : 0.00,
                (isset($request->post['price']) && $request->post['price'] != '') ? $request->post['price'] * 1.21 : 0.00,

                (isset($request->post['discountPrice']) && $request->post['discountPrice'] != '') ? $request->post['discountPrice'] : 0.00,
                (isset($request->post['discountPrice']) && $request->post['discountPrice'] != '') ? $request->post['discountPrice'] * 1.21 : 0.00,
            );
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_get_similar":
        
        $tpl->define(['edit_product' => '/admin_products/tpl/edit_product.html']);
        $tpl->split_template('edit_product', 'EDIT_PRODUCT');

        if(!isset($request->post['sku']) || !isset($request->post['itemId'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $related = [];
        $result = $db->query("SELECT * FROM shop_products_relation WHERE ITEM_ID = ?", $request->post['itemId'])->fetchAll();
        foreach($result as $row){
            $related[] = $row['RELATED_ITEM_ID'];
        }


        $tpl->assign("ORIGINAL_ITEM_ID", $request->post['itemId']);
        

        $result = $db->query("SELECT * FROM shop_products WHERE (SKU LIKE ? OR NEW_SKU LIKE ?) AND ITEM_ID != ?", $request->post['sku'] . '%', $request->post['sku'] . '%', $request->post['itemId'])->fetchAll();
        foreach($result as $row){

            if(in_array($row['ITEM_ID'], $related)){
                continue;
            }

            $tpl->assign_array($row);
            $tpl->assign("RELATED_ITEM_ID", $row['ITEM_ID']);
            $tpl->parse("AJAX_RESULT", '.similar');
        }

        if($tpl->get_assigned("AJAX_RESULT")){
            $response->setAjaxOutput(['success' => true, 'html' => $tpl->fetch("AJAX_RESULT")]);
            break;
        }

        $response->setAjaxOutput(['success' => true, 'html' => '']);    

    break;

    case "i_add_relation":

        if(!isset($request->post['original']) || !isset($request->post['related'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $db->query("INSERT IGNORE INTO shop_products_relation (ITEM_ID, RELATED_ITEM_ID) VALUES (?, ?)", $request->post['original'], $request->post['related']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $db->query("INSERT IGNORE INTO shop_products_relation (ITEM_ID, RELATED_ITEM_ID) VALUES (?, ?)", $request->post['related'], $request->post['original']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);    
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_delete_relation":

        if(!isset($request->post['original']) || !isset($request->post['related'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $db->query("DELETE FROM shop_products_relation WHERE ITEM_ID = ? AND RELATED_ITEM_ID = ?", $request->post['original'], $request->post['related']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $db->query("DELETE FROM shop_products_relation WHERE ITEM_ID = ? AND RELATED_ITEM_ID = ?", $request->post['related'], $request->post['original']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);    
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_set_flag":

        if(!$request->post['itemId'] || !$request->post['flag'] || !$request->post['value']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $value = $request->post['value'] == "true" ? 1 : 0;

        switch($request->post['flag']){
            case "hot":
                $db->query("UPDATE shop_products_flags SET HOT = ?, HOT_DATE = ? WHERE ITEM_ID = ?", $value, $value == 1 ? date("Y-m-d H:i:s") : '0000-00-00 00:00:00', $request->post['itemId']);
                break;
            case "featured":
                $db->query("UPDATE shop_products_flags SET FEATURED = ?, FEATURED_DATE = ? WHERE ITEM_ID = ?", $value, $value == 1 ? date("Y-m-d H:i:s") : '0000-00-00 00:00:00', $request->post['itemId']);
                break;
            case "new":
                $db->query("UPDATE shop_products_flags SET NEW = ?, NEW_DATE = ? WHERE ITEM_ID = ?", $value, $value == 1 ? date("Y-m-d H:i:s") : '0000-00-00 00:00:00', $request->post['itemId']);
                break;
            case "sale":
                $db->query("UPDATE shop_products_flags SET SALE = ?, SALE_DATE = ? WHERE ITEM_ID = ?", $value, $value == 1 ? date("Y-m-d H:i:s") : '0000-00-00 00:00:00', $request->post['itemId']);
                break;
            
            
            case "e9":
                $db->query("UPDATE shop_products_flags SET E9 = ? WHERE ITEM_ID = ?", $value, $request->post['itemId']);
                break;
            case "e20":
                $db->query("UPDATE shop_products_flags SET E20 = ? WHERE ITEM_ID = ?", $value, $request->post['itemId']);
                break;
            case "ip68":
                $db->query("UPDATE shop_products_flags SET IP68 = ? WHERE ITEM_ID = ?", $value, $request->post['itemId']);
                break;
            case "emc":
                $db->query("UPDATE shop_products_flags SET EMC = ? WHERE ITEM_ID = ?", $value, $request->post['itemId']);
                break;
            case "ece":
                $db->query("UPDATE shop_products_flags SET ECE = ? WHERE ITEM_ID = ?", $value, $request->post['itemId']);
                break;
        }
        
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_set_main_image":

        if(!$request->post['itemId'] || !$request->post['fileId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $db->query("UPDATE shop_products_files SET IS_MAIN = 0 WHERE ITEM_ID = ?", $request->post['itemId']);
        $db->query("UPDATE shop_products_files SET IS_MAIN = 1 WHERE ITEM_ID = ? AND FILE_ID = ?", $request->post['itemId'], $request->post['fileId']);

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_sort_images":

        if(!$request->post['itemId'] || !$request->post['sort']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $weight = 0;
        $db->query("UPDATE shop_products_files SET `ORDER` = 0 WHERE ITEM_ID = ?", $request->post['itemId']);
        foreach($request->post['sort'] as $fileId){
            $db->query("UPDATE shop_products_files SET `ORDER` = ? WHERE ITEM_ID = ? AND FILE_ID = ?", $weight, $request->post['itemId'], $fileId);
            $weight += 5;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_upload_product_image":

        if(!$request->post['itemId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }
        
        $filePublic = new \_class\Files(FILE_PUBLIC_PATH);
        $result = $filePublic->saveFile("file", $request->files);
        if(!$result){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $path = $filePublic->getPath($result);
        
        $imageResizer = new \products\ImageResizer;
        if(!$imageResizer->uploadAndAddToProduct($path, $request->post['itemId'])){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);    
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_upload_product_files":

        if(!$request->post['itemId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $filePublic = new \_class\Files(FILE_PUBLIC_PATH);

        $fileName = $filePublic->saveFileKeepName("file", $request->files);
        if(!$fileName){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        // $path = $filePublic->getSlug($fileName);
        $newPath = '/files_public/' . $fileName;

        $fileId = 0;
        $result = $db->query("SELECT MAX(FILE_ID) AS FILE_ID FROM shop_products_files")->fetchArray();
        if($result){
            $fileId = $result['FILE_ID'] + 1;
        }

        $db->query("INSERT INTO shop_products_files (FILE_ID, ITEM_ID, FILE_TYPE, FILE) VALUES (?, ?, 2, ?)", $fileId, $request->post['itemId'], $newPath);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);    
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_delete_image":

        if(!$request->post['itemId'] || !$request->post['fileId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $result = $db->query("SELECT * FROM shop_products_files WHERE ITEM_ID = ? AND FILE_ID = ?", $request->post['itemId'], $request->post['fileId'])->fetchAll();
        foreach($result as $row){
            if(file_exists(SERVER_PATH . $row['FILE'])){
                unlink(SERVER_PATH . $row['FILE']);
            }
        }

        $db->query("DELETE FROM shop_products_files WHERE ITEM_ID = ? AND FILE_ID = ?", $request->post['itemId'], $request->post['fileId']);

        $response->setAjaxOutput(['success' => true]);            

    break;

    case "i_add_new_attribute":

        $db->query("INSERT INTO shop_attributes () VALUES ()");
        $response->setAjaxOutput(['success' => true]);

    break;
    
    case "i_add_new_attribute_value":

        $db->query("INSERT INTO shop_attributes_values (ATTR_ID) VALUES (?)", $request->post['attrId']);
        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_save_attribute_translation":

        if(!$request->post['attrId'] || !$request->post['lang']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $db->query("INSERT IGNORE INTO shop_attributes_lang (ATTR_ID, LANG, ATTR_NAME) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE ATTR_NAME = ?", $request->post['attrId'], $request->post['lang'], $request->post['attrName'], $request->post['attrName']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $response->setAjaxOutput(['success' => true]);      

    break;

    case "i_save_attribute_value":

        if(!$request->post['attrId'] || !$request->post['valId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $db->query("UPDATE shop_attributes_values SET VALUE = ? WHERE VAL_ID = ? AND ATTR_ID = ?", $request->post['value'], $request->post['valId'], $request->post['attrId']);
        
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_get_attribute_values":

        if(!$request->post['attrId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $tpl->define(['edit_product' => '/admin_products/tpl/edit_product.html']);
        $tpl->split_template('edit_product', 'EDIT_PRODUCT');

        $result = $db->query("SELECT * FROM shop_attributes_values WHERE ATTR_ID = ?", $request->post['attrId'])->fetchAll();
        $data = [];
        foreach($result as $value){
            $data[$value['VAL_ID']] = $value['VALUE'];
        }

        $tpl->option_list("ATTRIBUTE_VALUES", "", $data);
        $tpl->parse("AJAX_RESULT", "select");

        $response->setAjaxOutput(['success' => true, 'html' => $tpl->fetch("AJAX_RESULT")]);

    break;

    case "i_add_product_attribute":

        if(!$request->post['attrId'] || !$request->post['valId'] || !$request->post['itemId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $db->query("INSERT IGNORE INTO shop_products_attributes (ITEM_ID, ATTR_ID, VAL_ID) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE VAL_ID = ?", $request->post['itemId'], $request->post['attrId'], $request->post['valId'], $request->post['valId']);
        
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_delete_product_attribute":

        if(!$request->post['attrId'] || !$request->post['itemId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $db->query("DELETE FROM shop_products_attributes WHERE ITEM_ID = ? AND ATTR_ID = ?", $request->post['itemId'], $request->post['attrId']);

        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_upload_category_image":

        if(!$request->post['categoryId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $filePublic = new \_class\Files(FILE_PUBLIC_PATH);
        $result = $filePublic->saveFile("file", $request->files);

        if(!$result){
            $response->setAjaxOutput(['success' => false, 'error' => 2, 'upload_error' => $filePublic->getError()]);    
            break;
        }

        // Delete old images

        $result2 = $db->query("SELECT * FROM shop_products_category_files WHERE CATEGORY_ID = ?", $request->post['categoryId'])->fetchAll();
        foreach($result2 as $row){
            if(file_exists(SERVER_PATH . $row['FILE'])){
                unlink(SERVER_PATH . $row['FILE']);
            }
        }

        $db->query("DELETE FROM shop_products_category_files WHERE CATEGORY_ID = ?", $request->post['categoryId']);

        // Delete old images


        $path = $filePublic->getPath($result);
        
        $imageResizer = new \products\ImageResizer;
        if(!$imageResizer->uploadAndAddToCategory($path, $request->post['categoryId'])){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);    
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_save_product_categories":

        if(!$request->post['itemId'] || !$request->post['category']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $db->query("DELETE FROM shop_products_category WHERE ITEM_ID = ?", $request->post['itemId']);
        foreach($request->post['category'] as $categoryId){
            $db->query("INSERT IGNORE INTO shop_products_category (ITEM_ID, CATEGORY_ID) VALUES (?, ?)", $request->post['itemId'], $categoryId);
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_delete_category":

        if(!$request->post['categoryId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $db->query("UPDATE shop_categories SET DELETED = 1 WHERE CATEGORY_ID = ?", $request->post['categoryId']);
        $response->setAjaxOutput(['success' => true]);

    break;
    
    case "i_change_category_status":

        if(!$request->post['categoryId'] || !isset($request->post['status'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        if(!in_array($request->post['status'], [0, 2])){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $db->query("UPDATE shop_categories SET STATUS = ? WHERE CATEGORY_ID = ?", $request->post['status'], $request->post['categoryId']);
        $response->setAjaxOutput(['success' => true]);

    break;
    
    case "i_change_product_status":

        if(!$request->post['itemId'] || !isset($request->post['status'])){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        switch($request->post['statusType']){
            case "delete":
                if(!in_array($request->post['status'], [0, 1])){
                    $response->setAjaxOutput(['success' => false, 'error' => 2]);    
                    break;
                }
        
                $db->query("UPDATE shop_products SET DELETED = ? WHERE ITEM_ID = ?", $request->post['status'], $request->post['itemId']);
                $response->setAjaxOutput(['success' => true]);
            break 2;
            
            case "shippable":
                if(!in_array($request->post['status'], [0, 1])){
                    $response->setAjaxOutput(['success' => false, 'error' => 2]);    
                    break;
                }
        
                $db->query("UPDATE shop_products SET SHIPPABLE = ? WHERE ITEM_ID = ?", $request->post['status'], $request->post['itemId']);
                $response->setAjaxOutput(['success' => true]);
            break 2;

            default:
                if(!in_array($request->post['status'], [0, 2])){
                    $response->setAjaxOutput(['success' => false, 'error' => 2]);    
                    break;
                }
        
                $db->query("UPDATE shop_products SET STATUS = ? WHERE ITEM_ID = ?", $request->post['status'], $request->post['itemId']);
                $response->setAjaxOutput(['success' => true]);
            break 2;
        }

        $response->setAjaxOutput(['success' => false, 'error' => 3]);    

    break;

    case "i_add_new_product":

        if(!$request->post['categoryId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $sku = rand(100000, 999999);
        $db->query("INSERT IGNORE INTO shop_products (SKU, NEW_SKU) VALUES (?, ?)", $sku, $sku);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $itemId = $db->lastInsertID();
        if(!$itemId){
            $response->setAjaxOutput(['success' => false, 'error' => 3]);    
            break;
        }

        $db->query("INSERT INTO shop_products_category (ITEM_ID, CATEGORY_ID) VALUES (?, ?)", $itemId, $request->post['categoryId']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 4]);    
            break;
        }

        $db->query("INSERT INTO shop_products_flags (ITEM_ID) VALUES (?)", $itemId);
        $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, DESCR) VALUES (?, 'lv', '')", $itemId);


        $response->setAjaxOutput(['success' => true, 'url' => $tpl->urlFor('admin_products/edit_product', ['itemId' => $itemId])]);

    break;

    case "i_copy_images":

        if(!$request->post['targetItemId'] || !$request->post['itemId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $product = $db->query("SELECT * FROM shop_products WHERE ITEM_ID = ?", $request->post['targetItemId'])->fetchArray();
        if(!$product){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }
    
        $data = [];
        $result = $db->query("SELECT * FROM shop_products_files WHERE ITEM_ID = ? AND FILE_TYPE = 1", $request->post['itemId'])->fetchAll();
        foreach($result as $row){
            $data[$row['FILE_ID']][] = $row;
        }

        foreach($data as $fileData){
            $fileId = 0;
            $result = $db->query("SELECT MAX(FILE_ID) AS FILE_ID FROM shop_products_files")->fetchArray();
            if($result){
                $fileId = $result['FILE_ID'] + 1;
            }

            foreach($fileData as $file){
                $path = '/files_public/product_images/' . $product['NEW_SKU'] . basename($file['FILE']);
                if(copy(PUBLIC_DIR . $file['FILE'], PUBLIC_DIR . $path)){
                    $db->query("INSERT INTO shop_products_files (FILE_ID, ITEM_ID, FILE_TYPE, FILE, IS_MAIN, IMAGE_SIZE) VALUES (?, ?, 1, ?, 0, ?)", $fileId, $request->post['targetItemId'], $path, $file['IMAGE_SIZE']);
                }
            }
        }

        // echo '<pre>'; print_r($data); echo '</pre>'; die;
        


        // echo '<pre>'; print_r($result); echo '</pre>';

        $response->setAjaxOutput(['success' => true]);

    break;


    case "i_load_products_for_copy":

        if(!$request->post['sku'] || !$request->post['itemId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $product = $db->query("SELECT * FROM shop_products WHERE ITEM_ID = ?", $request->post['itemId'])->fetchArray(); 
        if(!$product){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $result = $db->query("SELECT * FROM shop_products WHERE SKU LIKE ? OR NEW_SKU LIKE ? AND ITEM_ID != ?", $request->post['sku'] . '%', $request->post['sku'] . '%', $product['ITEM_ID'])->fetchAll();
        
        $tpl->define(['edit_product' => '/admin_products/tpl/edit_product.html']);
        $tpl->split_template('edit_product', 'EDIT_PRODUCT');

        foreach($result as $row){

            $photoCount = $db->query("SELECT COUNT(DISTINCT(FILE_ID)) AS COU FROM shop_products_files WHERE ITEM_ID = ? AND FILE_TYPE = 1", $row['ITEM_ID'])->fetchArray();

            $tpl->assign_array($row);
            $tpl->assign_array([
                "ORIGINAL_ITEM_ID" => $product['ITEM_ID'],
                "TARGET_ITEM_ID" => $row['ITEM_ID'],
                "COUNT" => $photoCount['COU'],
            ]);
            $tpl->parse("AJAX_RESULT", ".copy_list_item");
        }

        $html = '';
        if($tpl->get_assigned("AJAX_RESULT")){
            $html = $tpl->fetch("AJAX_RESULT");
        }

        $response->setAjaxOutput(['success' => true, 'html' => $html]);

    break;

    case "i_change_price":

        if(!$request->post['categoryId'] || !$request->post['percent']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        if($request->post['percent'] > 0){
            $db->query("UPDATE shop_products_prices
            INNER JOIN shop_products_category USING (ITEM_ID)
            SET DISCOUNT_PRICE = STANDART_PRICE + (STANDART_PRICE * ?)
            WHERE CATEGORY_ID = ?", abs($request->post['percent']) / 100, $request->post['categoryId']);
        } else {
            $db->query("UPDATE shop_products_prices
            INNER JOIN shop_products_category USING (ITEM_ID)
            SET DISCOUNT_PRICE = STANDART_PRICE - (STANDART_PRICE * ?)
            WHERE CATEGORY_ID = ?", abs($request->post['percent']) / 100, $request->post['categoryId']);
        }

        $db->query("UPDATE shop_products_prices
        INNER JOIN shop_products_category USING (ITEM_ID)
        SET DISCOUNT_PRICE_WITH_VAT = DISCOUNT_PRICE * 1.21
        WHERE CATEGORY_ID = ?", $request->post['categoryId']);
        
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        if(isset($request->post['saleFlag'])){
            $db->query("UPDATE shop_products_flags
            INNER JOIN shop_products_category USING (ITEM_ID)
            SET SALE = 1, SALE_DATE = now()
            WHERE CATEGORY_ID = ?", $request->post['categoryId']);
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_reset_price":

        if(!$request->post['categoryId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $db->query("UPDATE shop_products_prices
        INNER JOIN shop_products_category USING (ITEM_ID)
        SET DISCOUNT_PRICE = 0.00, DISCOUNT_PRICE_WITH_VAT = 0.00
        WHERE CATEGORY_ID = ?", $request->post['categoryId']);

        if($request->post['removeFlag'] == 'true'){
            $db->query("UPDATE shop_products_flags
            INNER JOIN shop_products_category USING (ITEM_ID)
            SET SALE = 0, SALE_DATE = '0000-00-00 00:00:00'
            WHERE CATEGORY_ID = ?", $request->post['categoryId']);
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_open_translation":

        if(!$request->post['itemId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $tpl->define(['grouped_product_translation' => '/admin_products/tpl/grouped_product_translation.html']);
        $tpl->split_template('grouped_product_translation', 'GROUPED_PRODUCT_TRANSLATION');

        $result = $db->query("SELECT * FROM shop_products
        LEFT JOIN shop_products_lang USING (ITEM_ID)
        WHERE LANG = 'lv' AND ITEM_ID = ?", $request->post['itemId'])->fetchArray();

        if(!$result){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $tpl->assign_array([
            "ITEM_ID" => $request->post['itemId'],
            "DESCR_LV" => $result['DESCR'],
        ]);

        $tpl->parse("HTML_RESULT", ".trans_result");
        $html = $tpl->fetch("HTML_RESULT");

        $response->setAjaxOutput(['success' => true, 'html' => $html]);

    break;

    case "i_save_translation":

        if(!$request->post['itemId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $result = $db->query("SELECT * FROM shop_products
        LEFT JOIN shop_products_lang USING (ITEM_ID)
        WHERE LANG = 'lv' AND ITEM_ID = ?", $request->post['itemId'])->fetchArray();

        $ids = $db->query("SELECT ITEM_ID FROM shop_products_lang WHERE DESCR = ? AND LANG = 'lv'", $result['DESCR'])->fetchAll();
        foreach($ids as $row){

            $product = $db->query("SELECT * FROM shop_products WHERE ITEM_ID = ?", $row['ITEM_ID'])->fetchArray();
            if(!$product) continue;

            if($request->post['descrru']){
                $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, 'ru', ?, '', ?)
                ON DUPLICATE KEY UPDATE DESCR = ?", $row['ITEM_ID'], $product['NEW_SKU'], $request->post['descrru'], $request->post['descrru']);
            }

            if($request->post['descren']){
                $db->query("INSERT INTO shop_products_lang (ITEM_ID, LANG, TITLE, SHORT_DESCR, DESCR) VALUES (?, 'en', ?, '', ?)
                ON DUPLICATE KEY UPDATE DESCR = ?", $row['ITEM_ID'], $product['NEW_SKU'], $request->post['descren'], $request->post['descren']);
            }
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_set_max_product_weight":

        if(!$request->post['itemId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $category = $db->query("SELECT * FROM shop_products_category WHERE ITEM_ID = ? LIMIT 1", $request->post['itemId'])->fetchArray();
        if(!$category){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $result = $db->query("SELECT MAX(WEIGHT_ORDER) AS MAX_WEIGHT_ORDER
        FROM shop_products
        INNER JOIN shop_products_category USING (ITEM_ID)
        WHERE CATEGORY_ID = ?", $category['CATEGORY_ID'])->fetchArray();

        $db->query("UPDATE shop_products SET WEIGHT_ORDER = ? + 1 WHERE ITEM_ID = ?", $result['MAX_WEIGHT_ORDER'] ?: 0, $request->post['itemId']);

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_add_additional_sku":

        if(!$request->post['itemId'] || !$request->post['sku']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $skuArr = explode(",", $request->post['sku']);

        foreach($skuArr as $sku){
            $db->query("INSERT IGNORE INTO shop_products_sku (ITEM_ID, SKU) VALUES (?, ?)", $request->post['itemId'], trim($sku));    
        }

        $response->setAjaxOutput(['success' => true]);

    break;
    
    case "i_remove_additional_sku":

        if(!$request->post['itemId'] || !$request->post['sku']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);    
            break;
        }

        $db->query("DELETE FROM shop_products_sku WHERE ITEM_ID = ? AND SKU = ?", $request->post['itemId'], $request->post['sku']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);    
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

    case "i_toggle_shippable":

        if(!$request->post['categoryId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $db->query("UPDATE shop_categories SET SHIPPABLE = !SHIPPABLE WHERE CATEGORY_ID = ?", $request->post['categoryId']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;
}

$response->output();
exit;