<?php

include_once('../_main.php');

switch ($a) {
    case 'i_edit_profile':

        $userData = $db->query("SELECT * FROM tbl_user WHERE UID = ?", $user->uid)->fetchArray();
        $userInfo = $db->query("SELECT * FROM tbl_user_info WHERE UID = ?", $user->uid)->fetchArray();

        if($request->post['fname']){
            $userData['FNAME'] = $request->post['fname'];
        }
        if($request->post['lname']){
            $userData['LNAME'] = $request->post['lname'];
        }
        if($request->post['email']){
            $userData['EMAIL'] = $request->post['email'];
        }
        if($request->post['phone']){
            $userInfo['PHONE'] = $request->post['phone'];
        }

        $db->query("UPDATE tbl_user SET FNAME = ?, LNAME = ?, EMAIL = ? WHERE UID = ?", $userData['FNAME'], $userData['LNAME'], $userData['EMAIL'], $user->uid);
        $db->query("UPDATE tbl_user_info SET PHONE = ? WHERE UID = ?", $userInfo['PHONE'], $user->uid);

        $response->setAjaxOutput(['success' => true]);
        
    break;


    case "i_save_edit_address":

        $required = ['name', 'surname', 'shippingAddress', 'country', 'city', 'state', 'zip', 'email', 'phone'];
        foreach($required as $field){
            if(!isset($request->post[$field])){
                $response->setAjaxOutput(['success' => false, 'error' => 1]);
                break 2;
            }
        }

        $recId = 0;

        if(!isset($request->post['recId']) || !$request->post['recId']){
            $db->query("INSERT INTO tbl_user_address (UID, NAME, SURNAME, COUNTRY, SHIPPING_ADDRESS, CITY, STATE, ZIP, EMAIL, PHONE)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                $user->uid, 
                $request->post['name'], 
                $request->post['surname'],
                $request->post['country'],
                $request->post['shippingAddress'],
                $request->post['city'],
                $request->post['state'],
                $request->post['zip'],
                $request->post['email'],
                $request->post['phone']
            );

            if($db->affectedRows() <= 0){
                $response->setAjaxOutput(['success' => false, 'error' => 2]);
                break;
            }

            $recId = $db->lastInsertID();
        } else {
            $db->query("UPDATE tbl_user_address SET NAME = ?, SURNAME = ?, COUNTRY = ?, SHIPPING_ADDRESS = ?, CITY = ?, STATE = ?, ZIP = ?, EMAIL = ?, PHONE = ? WHERE REC_ID = ? AND UID = ?", 
                $request->post['name'], 
                $request->post['surname'],
                $request->post['country'],
                $request->post['shippingAddress'],
                $request->post['city'],
                $request->post['state'],
                $request->post['zip'],
                $request->post['email'],
                $request->post['phone'],
                $request->post['recId'],
                $user->uid
            );

            $recId = $request->post['recId'];
        }

        if(!$recId){
            $response->setAjaxOutput(['success' => false, 'error' => 4]);
            break;
        }

        if($request->post['is_default']){
            $db->query("UPDATE tbl_user_address SET IS_DEFAULT = 0 WHERE UID = ?", $user->uid);
            $db->query("UPDATE tbl_user_address SET IS_DEFAULT = 1 WHERE UID = ? AND REC_ID = ?", $user->uid, $recId);
        }

        $response->setAjaxOutput(['success' => true, 'url' => $tpl->urlFor('profile/address_edit', ['recId' => $recId])]);
            
    break;

    case "i_remove_address":

        if(!$request->post['recId']){
            $response->setAjaxOutput(['success' => false, 'error' => 1]);
            break;
        }

        $db->query("DELETE FROM tbl_user_address WHERE UID = ? AND REC_ID = ?", $user->uid, $request->post['recId']);
        if($db->affectedRows() <= 0){
            $response->setAjaxOutput(['success' => false, 'error' => 2]);
            break;
        }

        $response->setAjaxOutput(['success' => true]);

    break;

}



$response->output();
exit;