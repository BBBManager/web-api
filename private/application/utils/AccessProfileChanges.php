<?php

class BBBManager_Util_AccessProfileChanges {
    protected static $_instance;
    protected $_fileName;
    
    public static function getInstance(){
        if(self::$_instance == null){
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    public function __construct(){
        $this->_fileName = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'accessProfileChanges';
    }
    
    public function getFileName(){
        return $this->_fileName;
    }
    
    public function mustChange() {
	touch($this->_fileName);
    }
    
    public function changesMade() {
        if(file_exists($this->_fileName)){
            unlink($this->_fileName);    
        }
    }
    
    public function getProgressFileName($uuid){
        $cacheDir = dirname($this->_fileName);
        return $cacheDir . DIRECTORY_SEPARATOR . session_id() . sha1($uuid);
    }
    
    public function cleanProgressFiles(){
        $cacheDir = dirname($this->_fileName);
        $progressFilePrefix = $cacheDir . DIRECTORY_SEPARATOR . session_id();
        
        $filesCollection = glob($progressFilePrefix . '*');
        
        foreach($filesCollection as $file){
            unlink($file);
        }
    }
}