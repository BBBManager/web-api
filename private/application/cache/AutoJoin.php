<?php
class BBBManager_Cache_AutoJoin{
    private $_cacheLifetime         = 31536000;
    private $_cacheStorageKey       = 'autoJoin';
    private $_cacheDir;
    
    private static $_instance;
    
    private $_iniFilePath;
    
    public static function getInstance(){
        if(self::$_instance == null){
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    public function __construct(){
        $this->_cacheDir    = APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'cache';
    }
    
    public function generateData(){
        return array('auto-join-keys' => array());
    }
    
    public function getData() {
        return IMDT_Util_Cache::getFromCache($this);
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
    
    public function clean(){
        return IMDT_Util_Cache::clean($this);
    }
    
    public function add($value){
        $currentData = $this->getData();
        $currentData['auto-join-keys'][key($value)] = current($value);
        
        IMDT_Util_Cache::setInCache($this, $currentData);
    }
}
