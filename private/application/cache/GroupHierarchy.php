<?php
class BBBManager_Cache_GroupHierarchy{
    private $_cacheStorageKey       = 'groupHierarchy';
    private $_cacheLifetime	    = 300;
    private $_cacheDir;
    
    private static $_instance;
    
    public static function getInstance(){
        if(self::$_instance == null){
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    public function __construct(){
        $this->_cacheDir = APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'cache';
    }
    
    public function generateData(){
        $groupsModel = new BBBManager_Model_Group();
	return $groupsModel->getGroupHierarchy();
    }
    
    public function getData() {
        return IMDT_Util_Cache::getFromCache($this);
    }
    
    public function clean(){
	return IMDT_Util_Cache::clean($this);
    }

    public function getCacheLifetime() {
        return $this->_cacheLifetime;
    }

    public function getCacheStorageKey() {
        return $this->_cacheStorageKey;
    }
    
    public function getCacheDir() {
        return $this->_cacheDir;
    }

    public function setCacheDir($cacheDir) {
        $this->_cacheDir = $cacheDir;
    }
}