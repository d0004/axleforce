
<?php

include_once('../_main.php');
include_once('./_config.php');

$tpl->define(['translations' => '/admin/tpl/translations.html']);
$tpl->split_template('translations', 'TRANSLATIONS');

function getDirContents($dir, &$results = array()) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $info = pathinfo($path);
            if($info['extension'] == "html"){
                $results[] = $path;
            }
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            $info = pathinfo($path);
            if($info['extension'] == "html"){
                $results[] = $path;
            }
        }
    }

    return $results;
}


foreach(getDirContents(APP_DIR) as $file){
    $fileName = str_replace("/home/admin/web/axleforce.lv/public_html/app", "", $file);
    $tpl->assign("FILE_NAME", $fileName);
    $tpl->assign("FILE_PATH", $file);

    $tpl->assign("IS_ADMIN_MODULE", false);
    if(strpos($fileName, 'admin') !== false) {
        $tpl->assign("IS_ADMIN_MODULE", true);
    }

    $tpl->parse("FILE_ROW", ".file_row");

}



$tpl->parse("CONTENT", "translations");
include_once('../_body_admin.php');