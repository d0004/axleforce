<?php

namespace _class;

class User {

    public $isOnline = false;
    public $userTokenData = [];
    public $userData = [];
    public $userInfo = [];
    public $userLegal = [];

    public $isLegal = false;
    
    public $uid = 0;
    public $status = ['1'];
    public $phpsessid;

    protected $db;
    protected $tpl;
    protected $request;

    protected $error;

    function __construct()
    {
        $this->db = \_class\Registry::load('db');
        $this->tpl = \_class\Registry::load('tpl');
        $this->request = \_class\Registry::load('request');
    }

    public function prepare()
    {

        if(!isset($_SESSION['UID'])){
            return true;
        }
    
        $this->userData = $this->db->query("SELECT * FROM tbl_user WHERE UID = ?", $_SESSION['UID'])->fetchArray();
        if(!$this->userData){
            return false;
        }

        $this->isOnline = true;
        $this->uid = $_SESSION['UID'];

        $this->userInfo = $this->db->query("SELECT * FROM tbl_user_info WHERE UID = ?", $this->uid)->fetchArray();
        $this->userLegal = $this->db->query("SELECT * FROM tbl_user_legal WHERE UID = ?", $this->uid)->fetchArray();

        $status = $this->db->query("SELECT STATUS FROM tbl_user_status WHERE UID = ? AND EXPIRE_DATE > NOW()", $this->uid)->fetchAll();
        $this->status = [];

        foreach($status as $row){
            $this->status[] = $row['STATUS'];
        }

        $this->tpl->assign_array([
            "USER_FNAME" => $this->userData['FNAME'],
            "USER_LNAME" => $this->userData['LNAME'],
            "USER_EMAIL" => $this->userData['EMAIL'],
        ]);

        if($this->userData){
            if($this->userData['IS_COMPANY']){
                if($this->hasStatus(2000)){
                    $this->isLegal = true;
                }
            }
        }
    
        return true;
    }

    public function logout()
    {
        if($_SESSION['ADMIN_UID']){
            $uid = $_SESSION['ADMIN_UID'];
            $_SESSION = [];
            $_SESSION['UID'] = $uid;    
        } else {
            $_SESSION = [];
            session_destroy();
        }
        return true;
    }

    public function login($uid)
    {
        $this->db->query("INSERT IGNORE INTO log_login (UID, SESSION_ID, IP, CREATE_DATE) VALUES (?, ?, ?, now())", $uid, session_id(), $this->request->server['REMOTE_ADDR']);
        $_SESSION['UID'] = $uid;
    }

    public function register($request)
    {
        
        do{
            $uid = rand(127010348, 999999999);
            $this->db->query("INSERT INTO tbl_user (UID, EMAIL, PASSWORD, FNAME, LNAME, VALIDATION_CODE) VALUES (?, ?, ?, ?, ?, ?)", $uid, $request->post['email'], md5(sha1($request->post['password'])), $request->post['fname'], $request->post['lname'], $this->generateRandomString(32));
        } while ($this->db->affectedRows() <= 0);

        $this->db->query("INSERT INTO tbl_user_info (UID) VALUES (?)", $uid);
        $this->setStatus($uid, 64);
        $this->setStatus($uid, 3);

        return $uid;
    }
    
    public function registerLegal($request)
    {

        if(!$request->post['vatNumber']){
            $this->error = 1;
            return false;
        }

        $checkVat = new \_class\CheckVat;
        $checkVat->check($request->post['vatNumber']);
        $result = $checkVat->getResult();

        if($result['errorCode']){
            $this->error = 2;
            return false;
        }

        if(!$result['name'] || !$result['address']){
            $this->error = 3;
            return false;
        }
        
        do{
            $uid = rand(127010348, 999999999);
            $this->db->query("INSERT INTO tbl_user (UID, EMAIL, PASSWORD, FNAME, LNAME, IS_COMPANY, VALIDATION_CODE) VALUES (?, ?, ?, ?, ?, 1, ?)", $uid, $request->post['email'], md5(sha1($request->post['password'])), $request->post['fname'], $request->post['lname'], $this->generateRandomString(32));
        } while ($this->db->affectedRows() <= 0);

        $this->db->query("INSERT INTO tbl_user_info (UID) VALUES (?)", $uid);
        
        $this->setLegalInfo(
            $uid,
            $result['vat'], 
            $result['name'], 
            $result['address']
        );
        
        $this->setStatus($uid, 64);
        $this->setStatus($uid, 3);
        return $uid;
    }

    public function setLegalInfo($uid, $vatNumber = '', $companyName = '', $companyAddress = '') 
    {
        $this->db->query("INSERT IGNORE INTO tbl_user_legal (UID) VALUES (?)", $uid);
        $this->db->query("UPDATE tbl_user_legal SET VAT_NUMBER = ?, COMPANY_NAME = ?, COMPANY_ADDRESS = ? WHERE UID = ?", 
            $vatNumber, 
            $companyName, 
            $companyAddress, 
            $uid
        );
        $this->setStatus($uid, 2000);
    }

    public function setStatus($uid, $status, $expireDate = '3000-01-01 00:00:00')
    {
        $this->db->query("INSERT INTO tbl_user_status (UID, STATUS, EXPIRE_DATE) VALUES (?, ?, ?)", $uid, $status, $expireDate);
        if($this->db->affectedRows() > 0){
            return true;
        }
        return false;
    }

    public function deleteStatus($uid, $status)
    {
        $this->db->query("DELETE FROM tbl_user_status WHERE UID = ? AND STATUS = ?", $uid, $status);
        if($this->db->affectedRows() > 0){
            return true;
        }
        return false;
    }
    
    public function hasStatus($status, $uid = '')
    {
        if(!$uid){
            if(in_array($status, $this->status)){
                return true;
            }
            return false;
        } else {
            $result = $this->db->query("SELECT STATUS FROM tbl_user_status WHERE UID = ? AND EXPIRE_DATE > NOW()", $uid)->fetchAll();
            $statusArr = [];
            foreach($result as $row){
                $statusArr[] = $row['STATUS'];
            }

            if(in_array($status, $statusArr)){
                return true;
            }
            return false;
        }

        return false;
    }

    public function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getError()
    {
        return $this->error;
    }
}