<?php
class BBBManager_Cache_GroupSync{
    private $_cacheStorageKey       = 'groupSync';
    private $_cacheLifetime;
    private $_cacheDir;
    
    private static $_instance;
    
    
    private $_authSettings;
    
    public static function getInstance(){
        if(self::$_instance == null){
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    public function __construct(){
        $this->_authSettings = BBBManager_Cache_Auth::getInstance()->getData();
        $this->_cacheLifetime = $this->_authSettings['ldap']['group_list_cache_ttl'];
        $this->_cacheDir = APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'cache';
    }
    
    public function generateData(){
        $ldapGroupsInfo = IMDT_Util_Ldap::getInstance()->fetchAllGroups();
	$ldapGroupsCollection = $ldapGroupsInfo['groups'];
	$ldapGroupsMemberOfMapping = $ldapGroupsInfo['groupsMemberOfMapping'];
	
	$ldapGroupNamesCollection = array();
	foreach($ldapGroupsCollection as $ldapGroup){
	    $ldapGroupNamesCollection[] = $ldapGroup['name'];
	}
	
	$groupsModel = new BBBManager_Model_Group();
	$ldapGroupsInDatabase = $groupsModel->findLdapGroups();
	$rLdapGroupsInDatabase = ($ldapGroupsInDatabase != null ? $ldapGroupsInDatabase->toArray() : array());
	$rDbGroupNameXDbGroupInfo = array();
	
	foreach($rLdapGroupsInDatabase as $rLdapGroupInDatabase){
	    $rDbGroupNameXDbGroupInfo[$rLdapGroupInDatabase['name']] = $rLdapGroupInDatabase;
	}
	
	$groupsInLdapAndNotInDb = array();
	$groupsInDbAndNotInLdap = array();
	
	foreach($rDbGroupNameXDbGroupInfo as $groupName => $groupInfo){
	    if(array_search($groupName, $ldapGroupNamesCollection) === false){
		$groupsInDbAndNotInLdap[] = $groupInfo['group_id'];
	    }
	}
	foreach($ldapGroupsCollection as $ldapGroup){
	    if(! isset($rDbGroupNameXDbGroupInfo[$ldapGroup['name']])){
		$groupsInLdapAndNotInDb[] = $ldapGroup;
	    }
	}

	$alreadyInserted = array();
	
	/* Create groups that are in AD and not yet in DB */
	foreach($groupsInLdapAndNotInDb as $ldapGroup){
	    if(isset($alreadyInserted[$ldapGroup['name']])){
		continue;
	    }
	    
	    $groupsModel->insert(array(
		'name'			=> $ldapGroup['name'],
		'auth_mode_id'		=> BBBManager_Config_Defines::$LDAP_AUTH_MODE,
		'access_profile_id'	=> BBBManager_Config_Defines::$NA_PROFILE,
		'visible'		=> $ldapGroup['visible']
	    ));
	    
	    $alreadyInserted[$ldapGroup['name']] = true;
	}
	
	if(count($groupsInDbAndNotInLdap) > 0){
	    $groupsModel->deleteById($groupsInDbAndNotInLdap);
	}
	
	$ldapGroupsInDatabase = $groupsModel->findLdapGroups();
	$rLdapGroupsInDatabase = ($ldapGroupsInDatabase != null ? $ldapGroupsInDatabase->toArray() : array());
	$rLdapGroupNameXIdMapping = array();
	
	foreach($rLdapGroupsInDatabase as $ldapGroupsInDatabase){
	    $rLdapGroupNameXIdMapping[$ldapGroupsInDatabase['name']] = $ldapGroupsInDatabase['group_id'];
	}
	
	$groupGroupModel = new BBBManager_Model_GroupGroup();
        $groupGroupCollection = $groupGroupModel->fetchAll();
        $rGroupGroupCollection = (($groupGroupCollection instanceof Zend_Db_Table_Rowset) ? $groupGroupCollection->toArray() : array());
        $groupGroupAlreadyInDb = array();
        
        foreach($rGroupGroupCollection as $groupGroupItem){
            $groupGroupAlreadyInDb[$groupGroupItem['group_id'] . '-' . $groupGroupItem['parent_group_id']] = true;
        }

	foreach($ldapGroupsMemberOfMapping as $groupName => $parentNames){
	    $childGroupId = $rLdapGroupNameXIdMapping[$groupName];
	    $groupGroupModel->deleteLdapGroupHierarchy($childGroupId);
	    
	    foreach($parentNames as $parentName){
                $parentGroupId = $rLdapGroupNameXIdMapping[$parentName];
                
                if(isset($groupGroupAlreadyInDb[$childGroupId.'-'.$parentGroupId])){
                    continue;
                }
                
		$groupGroupModel->insert(array(
		    'group_id'		    => $childGroupId,
		    'auth_mode_id'	    => BBBManager_Config_Defines::$LDAP_AUTH_MODE,
		    'parent_group_id'	    => $parentGroupId,
		    'parent_auth_mode_id'   => BBBManager_Config_Defines::$LDAP_AUTH_MODE
		));
                
                $groupGroupAlreadyInDb[$childGroupId.'-'.$parentGroupId] = true;
	    }
	}
	
	return true;
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