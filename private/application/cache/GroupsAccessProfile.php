<?php
class BBBManager_Cache_GroupsAccessProfile{
    private $_cacheStorageKey       = 'groupsAccessProfile';
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

	$allGroups = $groupsModel->fetchAll();
	$rAllGroups = ($allGroups != null ? $allGroups->toArray() : array());
	
	$groupsHierarchy = BBBManager_Cache_GroupHierarchy::getInstance()->getData();
	
	$groupXAccessProfileMapping = array();
	
	foreach($rAllGroups as $group){
	    if(isset($groupsHierarchy[$group['group_id']])){
		foreach($groupsHierarchy[$group['group_id']]['access_profile_id'] as $groupAccessProfile){
		    $groupXAccessProfileMapping[$group['group_id']][] = $groupAccessProfile;
		}
	    }else{
		$groupXAccessProfileMapping[$group['group_id']][] = $group['access_profile_id'];
	    }
	}
	
	return $groupXAccessProfileMapping;
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