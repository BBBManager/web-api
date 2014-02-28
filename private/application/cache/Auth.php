<?php
class BBBManager_Cache_Auth{
    private $_cacheLifetime         = 7200;
    private $_cacheStorageKey       = 'authSettings';
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
        $this->_iniFilePath = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'auth.ini';
    }
    
    public function generateData(){
        $configData = array();
        
        if(file_exists($this->_iniFilePath)){
            $configIni = new Zend_Config_Ini($this->_iniFilePath);
            $configData = array();
            
            if($configIni instanceof Zend_Config){
                $configData = $configIni->toArray();
            }
        }else{
            throw new Exception('authIniFile not found');
        }
        
        return $configData;
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
    
    public function getIniFilePath() {
        return $this->_iniFilePath;
    }

    public function setIniFilePath($iniFilePath) {
        $this->_iniFilePath = $iniFilePath;
    }
    
    public function clean(){
        return IMDT_Util_Cache::clean($this);
    }
}