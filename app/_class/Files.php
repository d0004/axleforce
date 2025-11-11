<?php

namespace _class;

class Files
{

    protected $dir;
    protected $maxFileSize = 134217728;
    protected $error = 0;

    protected $db;
    protected $uid;

    protected $level = 5;
    protected $private = 0;

    protected $keepOriginalFileName = false;

    function __construct($dir)
    {
        $this->dir = $dir;
    }

    public function setDb($db)
    {
        $this->db = $db;
    }

    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    public function getError()
    {
        return $this->error;
    }

    public function saveFile($requestFileName, $file)
    {

        $newFileName = $this->getUniqueFileName($file[$requestFileName]["name"]);
        $nfol = str_split($newFileName, 2);
        $newPath = '';

        for ($i=0; $i < $this->level; $i++) {
            $newPath.=$nfol[$i]."/";
        }

        $target_file = $this->dir . $newPath;
        $this->checkDir($target_file);

        //check extension TODO
        
        if ($file[$requestFileName]["size"] > $this->maxFileSize) {
            $this->error = 1;
            return false;
        }


        if (is_dir($target_file) && is_writable($target_file)) {
            if (move_uploaded_file($file[$requestFileName]["tmp_name"], $target_file . $newFileName)) {
                if($this->db instanceof \_class\db){
                    $this->db->query("INSERT INTO files (`FILE_NAME`, FILE_NAME_ORIGINAL, UID, PRIVATE) VALUES (?, ?, ?, ?)", $newFileName, $file[$requestFileName]["name"], $this->uid ? $this->uid : 0, $this->private);
                }
                return $newFileName;
            } else {
                $this->error = 2;
                return false;
            }
        } else {
            $this->error = 3;
            return false;
        }
    }

    public function saveFileTmp($requestFileName, $file)
    {

        $newFileName = $this->getUniqueFileName($file[$requestFileName]["name"]);
        $nfol = str_split($newFileName, 2);
        $newPath = 'tmp/';

        $target_file = $this->dir.$newPath;
        $this->checkDir($target_file);

        //check extension TODO
        
        if ($file[$requestFileName]["size"] > $this->maxFileSize) {
            $this->error = 1;
            return false;
        }

        if (move_uploaded_file($file[$requestFileName]["tmp_name"], $target_file . $newFileName)) {
            if($this->db instanceof \_class\db){
                $this->db->query("INSERT INTO files (`FILE_NAME`, FILE_NAME_ORIGINAL, UID, PRIVATE) VALUES (?, ?, ?, ?)", $newFileName, $file[$requestFileName]["name"], $this->uid ? $this->uid : 0, $this->private);
            }
            return $newFileName;
        } else {
            $this->error = 2;
            return false;
        }
    }

    public function saveFileKeepName($requestFileName, $file)
    {
        $originalName = $file[$requestFileName]["name"];
        $newFileName = $this->getUniqueFileName($originalName);
        $nfol = str_split($newFileName, 2);
        $newPath = '';

        for ($i=0; $i < $this->level; $i++) {
            $newPath.=$nfol[$i]."/";
        }

        $target_file = $this->dir.$newPath;
        $this->checkDir($target_file);

        //check extension TODO
        
        if ($file[$requestFileName]["size"] > $this->maxFileSize) {
            $this->error = 1;
            return false;
        }

        if (move_uploaded_file($file[$requestFileName]["tmp_name"], $target_file . $originalName)) {
            if($this->db instanceof \_class\db){
                $this->db->query("INSERT INTO files (`FILE_NAME`, FILE_NAME_ORIGINAL, UID, PRIVATE) VALUES (?, ?, ?, ?)", $newFileName, $file[$requestFileName]["name"], $this->uid ? $this->uid : 0, $this->private);
            }
            return $newPath . $originalName;
        } else {
            $this->error = 2;
            return false;
        }
    }


    protected function getUniqueFileName($fileName)
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $file = str_replace(".".$ext, "", $fileName);
        $result = md5($file.$this->uid.microtime(TRUE)).".".$ext;
        $result = strtolower($result);
        return $result;
    }

    public function getPath($fileName)
    {
        $newPath = '';
        $nfol = str_split($fileName, 2);
        for ($i=0; $i < $this->level; $i++) {
            $newPath.=$nfol[$i]."/";
        }

        if(file_exists($this->dir . $newPath . $fileName)){
            return $this->dir . $newPath . $fileName;
        } 
        
        return false;
    }

    public function getTmpPath($fileName)
    {
        $newPath = 'tmp/';
        if(file_exists($this->dir . $newPath . $fileName)){
            return $this->dir . $newPath . $fileName;
        } 
        
        return false;
    }

    public function getSlug($fileName)
    {
        $newPath = '';
        $nfol = str_split($fileName, 2);
        for ($i=0; $i < $this->level; $i++) {
            $newPath.=$nfol[$i]."/";
        }

        if(file_exists($this->dir . $newPath . $fileName)){
            return $newPath . $fileName;
        } 
        
        return false;

    }

    protected function checkDir($dir = "")
    {
        if ($dir == "") {
            $dir = $this->dir;
        }

        if (!is_dir($dir)) {
            $tmp_p = explode("/", $dir);
            unset($tmp_p[0]);
            $tmp = "/";
            foreach ($tmp_p as $value) {
                if($value == "") continue;
                $tmp .= $value."/";
                if (!is_dir($tmp)) {
                    mkdir($tmp, 0777);
                }
            }
        }
    }
}
