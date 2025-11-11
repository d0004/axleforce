<?php

include_once('../_main.php');
include_once('./_config.php');



$date = date("Y-m-d_H-i-s");
$path = FILE_PRIVATE_PATH . 'tmp/invoices/' . $date;
@mkdir($path, 0777, true);


$result = $db->query("SELECT * 
FROM tbl_bill 
INNER JOIN tbl_bill_to_order USING (BILL_ID)
LEFT JOIN tbl_order_admin_status USING (ORDER_ID)
WHERE STATUS = 2 AND (PAYMENT_DATE BETWEEN ? AND ?) AND (ADMIN_STATUS != 1 OR ADMIN_STATUS IS NULL)", $request->post['from'] . ' 00:00:00', $request->post['to'] . ' 23:59:59')->fetchAll();

if(!$result){
    echo "Invoices not found in this time interval"; die;
}

foreach($result as $row){
    $pdfClass = new \payment\pdf\invoice\ShopPayment;
    $pdfClass->saveTmp($path);
    $pdfClass->getDocument($row['BILL_ID']);
}

$zip = FILE_PRIVATE_PATH . 'tmp/invoices/' . $date . '.zip';

if(createZip($path, $zip)){
    header('Content-disposition: attachment; filename=' . basename($zip));
    header('Content-type: application/zip');
    readfile($zip);

    exec("rm " . $zip);
    exec("rm -r " . $path);
} else {
    echo json_encode(['success' => false, 'error' => 1]);
    die;
}



function createZip($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                continue;

            $file = realpath($file);

            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}

