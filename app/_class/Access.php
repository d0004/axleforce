<?php

namespace _class;

class Access
{

    protected $db;
    protected $user;

    function __construct(\_class\db $db, \_class\User $user)
    {
        $this->db = $db;
        $this->user = $user;
    }

    public function checkAccessFile($dir, $file)
    {

        if(empty($this->user->status)){
            return false;
        }

        $result = $this->db->query("SELECT * FROM access_file WHERE MODULE = ? AND FILE = ?", $dir, $file)->fetchAll();
        if(empty($result)){
            return false;
        }
        $allowedStatuses = [];
        foreach($result as $row){
            $allowedStatuses[] = $row['STATUS'];
        }

        foreach($this->user->status as $status){
            if(in_array($status, $allowedStatuses)){
                return true;
            }
        }

        if(in_array(40, $this->user->status)){
            var_dump($dir, $file);
        }

        return false;
    }

    public function checkAccessI($dir, $a)
    {
        $result = $this->db->query("SELECT * FROM access_i WHERE MODULE = ? AND `CASE` = ?", $dir, $a)->fetchAll();
        if(empty($result)){
            return false;
        }
        $allowedStatuses = [];
        foreach($result as $row){
            $allowedStatuses[] = $row['STATUS'];
        }
        
        if(empty($this->user->status)){
            return false;
        }

        foreach($this->user->status as $status){
            if(in_array($status, $allowedStatuses)){
                return true;
            }
        }
        
    }

}