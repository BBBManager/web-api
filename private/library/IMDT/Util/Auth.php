<?php
class IMDT_Util_Auth{
    private static $_instance;
    private $_authData;
    
    public static function getInstance(){
        if(self::$_instance == null){
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    public function __construct(){
        $authDataNs = new Zend_Session_Namespace('authData');
	$this->_authData = $authDataNs->authData;
    }
    
    public function get($key){
        return (isset($this->_authData[$key]) ? $this->_authData[$key] : null);
    }
    
    public function getData(){
	return $this->_authData;
    }
    
    public function getDataNoAcl(){
	$rt = array();
	
	foreach($this->_authData as $key => $value){
	    if($key == 'acl'){
		continue;
	    }
	    $rt[$key] = $value;
	}
	
	return $rt;
    }
}