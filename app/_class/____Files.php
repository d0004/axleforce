<?php

namespace _class;

class Files
{

    protected $module = '';
    protected $maxSize = 5000000;
    protected $db;

    function __construct()
    {
        $this->db = \_class\Registry::load('db');
    }

    public function setModule($module)
    {
        $this->module = $module;
    }

    public function saveUploadFile($file, $name)
    {
        $target_dir = APP_DIR . "/uploads/";
        $webPath = '/uploads/';
        if($this->module){
            $target_dir = $target_dir . $this->module . '/';
            if(!is_dir($target_dir)){
                mkdir($target_dir);
                $webPath = '/uploads/' . $this->module . '/';
                if(!is_dir($target_dir)){
                    return false;
                } 
            } else {
                $webPath = '/uploads/' . $this->module . '/';
            }
        }

        
        $target_file = $target_dir . basename($file["name"][$name]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $randomString = $this->generateRandomFileName();
        $newFilename = $randomString . '.' . $imageFileType;
        
        while($this->db->query("SELECT * FROM tbl_files WHERE FILE_NAME = ?", $newFilename)->fetchArray()){
            $randomString = $this->generateRandomFileName();
            $newFilename = $randomString . '.' . $imageFileType;
        }

        $target_file = $target_dir . basename($newFilename);
        $webPath .= basename($newFilename);


        $check = getimagesize($file["tmp_name"][$name]);
        if($check == false) {
            return false;
        }
        
        if (file_exists($target_file)) {
            return false;
        }
        
        if ($file["size"][$name] > $this->maxSize) {
            return false;
        }
        
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
            return false;
        }
       
        if (move_uploaded_file($file["tmp_name"][$name], $target_file)) {
            $this->db->query("INSERT INTO tbl_files (FILE_NAME, PATH, WEB_PATH) VALUES (?, ?, ?)", basename($target_file), $target_file, $webPath);
            $fileId = $this->db->lastInsertID();
            if($fileId){
                return $fileId;
            } else {
                return false;
            }
        } else {
            return false;
        }

        return false;
    }

    public function saveUploadFileFromGroup($file, $name, $i)
    {

        if($file["size"][$i][$name] > 0){
            
        } else {
            return 'empty';
        }

        $target_dir = APP_DIR . "/uploads/";
        $webPath = '/uploads/';
        if($this->module){
            $target_dir = $target_dir . $this->module . '/';
            if(!is_dir($target_dir)){
                mkdir($target_dir);
                $webPath = '/uploads/' . $this->module . '/';
                if(!is_dir($target_dir)){
                    return false;
                } 
            } else {
                $webPath = '/uploads/' . $this->module . '/';
            }
        }

        
        $target_file = $target_dir . basename($file["name"][$i][$name]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $randomString = $this->generateRandomFileName();
        $newFilename = $randomString . '.' . $imageFileType;
        
        while($this->db->query("SELECT * FROM tbl_files WHERE FILE_NAME = ?", $newFilename)->fetchArray()){
            $randomString = $this->generateRandomFileName();
            $newFilename = $randomString . '.' . $imageFileType;
        }

        $target_file = $target_dir . basename($newFilename);
        $webPath .= basename($newFilename);


        $check = getimagesize($file["tmp_name"][$i][$name]);
        if($check == false) {
            return false;
        }
        
        if (file_exists($target_file)) {
            return false;
        }
        
        if ($file["size"][$i][$name] > $this->maxSize) {
            return false;
        }
        
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
            return false;
        }
       
        if (move_uploaded_file($file["tmp_name"][$i][$name], $target_file)) {
            $this->db->query("INSERT INTO tbl_files (FILE_NAME, PATH, WEB_PATH) VALUES (?, ?, ?)", basename($target_file), $target_file, $webPath);
            $fileId = $this->db->lastInsertID();
            if($fileId){
                return $fileId;
            } else {
                return false;
            }
        } else {
            return false;
        }

        return false;
    }

    private function generateRandomFileName($length = 15) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}