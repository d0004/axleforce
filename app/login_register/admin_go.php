<?php

include_once('../_main.php');

if($request->post['uid']){
    $uid = $request->post['uid'];
}

$result = $db->query("SELECT * FROM tbl_user WHERE UID = ?", $uid)->fetchArray();
if(!$result){
    $response->redirect($tpl->urlFor('admin/index'));
    die;
}

if($uid){
    $_SESSION['ADMIN_UID'] = $_SESSION['UID'];
    $_SESSION['UID'] = $uid;
    $_SESSION['LOGOUT_REDIRECT'] = $request->post['route'];
}

$response->redirect($tpl->urlFor('index'));
die;