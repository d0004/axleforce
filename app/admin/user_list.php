<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['user_list' => '/admin/tpl/user_list.html']);
$tpl->split_template('user_list', 'USER_LIST');

$result = $db->query("SELECT *
FROM tbl_user
LEFT JOIN tbl_user_legal USING (UID)
ORDER BY tbl_user.CREATE_DATE DESC")->fetchAll();

foreach($result as $row){
    $tpl->assign_array($row);

    $billCount = $db->query("SELECT COUNT(*) AS COU FROM tbl_bill WHERE UID = ?", $row['UID'])->fetchArray();
    $billCountPaid = $db->query("SELECT COUNT(*) AS COU FROM tbl_bill WHERE UID = ? AND STATUS = 2", $row['UID'])->fetchArray();

    $tpl->assign_array([
        "BILL_COUNT" => $billCount['COU'],
        "BILL_COUNT_PAID" => $billCountPaid['COU'],
    ]);

    $totalPaid = $db->query("SELECT SUM(AMOUNT) AS TOTAL FROM tbl_bill WHERE STATUS = 2 AND UID = ?", $row['UID'])->fetchArray();
    $totalWaiting = $db->query("SELECT SUM(AMOUNT) AS TOTAL FROM tbl_bill WHERE STATUS = 0 AND UID = ?", $row['UID'])->fetchArray();
    $tpl->assign("TOTAL_AMOUNT", $totalPaid['TOTAL'] ?: 0.00);
    $tpl->assign("WAITING_AMOUNT", $totalWaiting['TOTAL'] ?: 0.00);

    $tpl->assign("BG_COLOR", '');
    if($billCountPaid['COU'] > 0){
        $tpl->assign("BG_COLOR", '#00d91b3b');
    }

    $tpl->assign("EMAIL_VALIDATED", true);
    if($user->hasStatus(3, $row['UID'])){
        $tpl->assign("EMAIL_VALIDATED", false);
    }

    $activeBill = $db->query("SELECT COUNT(AMOUNT) AS COU FROM tbl_bill WHERE STATUS = 0 AND UID = ?", $row['UID'])->fetchArray();
    $tpl->assign("ACTIVE_BILL", $activeBill['COU'] ?: 0);

    if($activeBill['COU'] > 0){
        $tpl->assign("BG_COLOR", '#ffbc003b');
    }

    $tpl->assign("UNSUBSCRIBED", false);
    $unsubscribe = $db->query("SELECT * FROM email_unsubscribe WHERE EMAIL = ?", $row['EMAIL'])->fetchArray();
    if($unsubscribe){
        $tpl->assign("UNSUBSCRIBED", true);
    }

    $tpl->parse("USER_ROW", ".user_row");
}

$tpl->parse("CONTENT", "user_list");
include_once('../_body_admin.php');