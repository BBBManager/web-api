<?php

class IMDT_Service_Auth_Adapter_Ldap {

    private $_settings;
    private $_userModel;
    private $_groupModel;

    public function __construct() {
	$this->_userModel = new BBBManager_Model_User();
	$this->_groupModel = new BBBManager_Model_Group();
    }

    public function authenticate($username, $password) {
	if ($this->_settings == null) {
	    $this->_settings = IMDT_Service_Auth::getInstance()->getSettings();
	}

	if ((!isset($this->_settings['ldap']['host'])) || (!isset($this->_settings['ldap']['base_dn'])) || (!isset($this->_settings['ldap']['username'])) || (!isset($this->_settings['ldap']['password']))) {
	    throw new Exception(__CLASS__ . ', invalid parameters, you must define parameters: host, base_dn, username and password in auth.ini file.');
        }

	$ldapAdapterConfig = array('server' => array());

	foreach ($this->_settings['ldap'] as $key => $value) {
	    if (in_array($key, array('key_mapping', 'group_list_cache_ttl', 'group_list_exclude', 'groups_from_user_query', 'users_from_group_query', 'all_groups_filter_query', 'users_sync_query'))) {
		continue;
	    }
	    $ldapAdapterConfig['server'][IMDT_Util_String::underscoreToCamelCase($key)] = $value;
	}
	
	$ldapConnection = new Zend_Ldap($ldapAdapterConfig['server']);
	$ldapConnection->connect();
	$ldapResult = $ldapConnection->search('(sAMAccountName=' . $username . ')');
	$userDn = $ldapResult->dn();
	
	$zfAuthAdapter = new Zend_Auth_Adapter_Ldap($ldapAdapterConfig, $userDn, $password);
	$zfAuthResult = Zend_Auth::getInstance()->authenticate($zfAuthAdapter);
	$authResult = new IMDT_Service_Auth_Result($zfAuthResult);
	
	if ($authResult->isValid()) {
	    $ldapBoundUser = IMDT_Util_Ldap::ldapNameToCleanName($zfAuthAdapter->getLdap()->getBoundUser());
	    $ldapGroupsNames = IMDT_Util_Ldap::getInstance()->fetchGroupsFromUser($ldapBoundUser);
	    
	    $groupsHierarchy = BBBManager_Cache_GroupHierarchy::getInstance()->getData();
	    $groupNameXHierarchy = array();
	    
	    foreach($groupsHierarchy as $group){
		$groupNameXHierarchy[$group['name']] = $group;
	    }
	    
	    $fullUserLdapGroups = array();
	    
	    foreach($ldapGroupsNames as $ldapGroupName){
		$fullUserLdapGroups[] = $ldapGroupName;
		if(isset($groupNameXHierarchy[$ldapGroupName]) && isset($groupNameXHierarchy[$ldapGroupName]['parents'])){
		    foreach($groupNameXHierarchy[$ldapGroupName]['parents'] as $ldapGroupParent){
			$fullUserLdapGroups[] = $ldapGroupParent;
		    }
		}
	    }
	    
	    if(is_array($fullUserLdapGroups) && (count($fullUserLdapGroups) > 0)){
		$localGroupsWithLdapMembers = $this->_groupModel->findLocalGroupsByLdapGroup($fullUserLdapGroups);
		$rLocalGroupsWithLdapMembers = ($localGroupsWithLdapMembers != null ? $localGroupsWithLdapMembers->toArray() : array());
	    }else{
		$rLocalGroupsWithLdapMembers = array();
	    }

	    if (count($rLocalGroupsWithLdapMembers) == 0) {
		/*$authResult->setIsValid(false);
		return $authResult;*/
		throw new Exception(IMDT_Util_Translate::_('Access denied for your user.'));
	    }

	    $ldapGroupsInDatabase = $this->_groupModel->findLdapGroups($ldapGroupsNames);
	    $rLdapGroupsInDatabase = ($ldapGroupsInDatabase != null ? $ldapGroupsInDatabase->toArray() : array());

	    $userInfo = array();
	    $userInfo['localGroupsFromLdapGroups'] = $rLocalGroupsWithLdapMembers;

	    if (count($rLdapGroupsInDatabase) > 0) {
		$userInfo['ldapGroupsInDatabase'] = array();

		foreach ($rLdapGroupsInDatabase as $dbLdapGroup) {
		    $userInfo['ldapGroupsInDatabase'][] = $dbLdapGroup;
		}
	    }

	    if (isset($this->_settings['ldap']['key_mapping']) && (is_array($this->_settings['ldap']['key_mapping']))) {
		$authResultObject = $zfAuthAdapter->getAccountObject();
		$authResultArray = array();

		foreach ($authResultObject as $k => $v) {
		    $authResultArray[$k] = $v;
		}

		foreach ($this->_settings['ldap']['key_mapping'] as $key => $value) {
		    $userInfo[$key] = IMDT_Util_String::replaceTags($value, $authResultArray, true);
                    
                    if($key == 'full_name'){
                        $userInfo[$key] = IMDT_Util_String::camelize($userInfo[$key]);
                    }
		}
	    } else {
		foreach ($authResultObject as $k => $v) {
		    $userInfo[$k] = $v;
		}
	    }
	    
	    $isUserInDatabase = $this->_checkLocalUser($username);

	    if ($isUserInDatabase == null) {
		$userInfo['auth_mode'] = BBBManager_Config_Defines::$LDAP_AUTH_MODE;
		$userInfo['access_profile_id'] = BBBManager_Config_Defines::$SYSTEM_USER_PROFILE;

		$apiData = array(
		    'name' => IMDT_Util_String::camelize($userInfo['full_name']),
		    'login' => strtolower($userInfo['login']),
		    'email' => $userInfo['email'],
		    'access_profile_id' => $userInfo['access_profile_id'],
		    'auth_mode_id' => $userInfo['auth_mode'],
		    'ldap_cn' => $ldapBoundUser
		);

		$userId = $this->_userModel->insert($apiData);
		$userInfo['id'] = $userId;
	    } else {
		$rUpdateDbFromLdap = array();
		
		$userDbData = $isUserInDatabase->toArray();
		
		if($userDbData['name'] != IMDT_Util_String::camelize($userInfo['full_name'])){
		    $rUpdateDbFromLdap['name'] = IMDT_Util_String::camelize($userInfo['full_name']);
		}
		
		if($userDbData['login'] != $userInfo['login']){
		    $rUpdateDbFromLdap['login'] = $userInfo['login'];
		}
		
		if($userDbData['email'] != $userInfo['email']){
		    $rUpdateDbFromLdap['email'] = $userInfo['email'];
		}
		
		if($userDbData['ldap_cn'] != $ldapBoundUser){
		    $rUpdateDbFromLdap['ldap_cn'] = $ldapBoundUser;
		}
		
		if(count($rUpdateDbFromLdap) > 0){
		    try{
			$whereUserId = $this->_userModel->getAdapter()->quoteInto('user_id = ?', $userDbData['user_id']);
			$this->_userModel->update($rUpdateDbFromLdap, $whereUserId);
		    }catch(Exception $e){
			throw new Exception($e->getMessage());
		    }
		}

		$userInfo['id'] = $userDbData['user_id'];
		$userInfo['auth_mode'] = $userDbData['auth_mode_id'];
		$userInfo['access_profile_id'] = $userDbData['access_profile_id'];
	    }

	    $authResult->setAuthData($userInfo);
	}

	return $authResult;
    }

    public function getSettings() {
	return $this->_settings;
    }

    public function setSettings($settings) {
	$this->_settings = $settings;
    }

    public function getAuthResult() {
	return $this->_authResult;
    }

    public function setAuthResult($authResult) {
	$this->_authResult = $authResult;
    }

    public function getIsValid() {
	return $this->_isValid;
    }

    public function setIsValid($isValid) {
	$this->_isValid = $isValid;
    }

    public function getAuthData() {
	return $this->_authData;
    }

    public function setAuthData($authData) {
	$this->_authData = $authData;
    }

    private function _checkLocalUser($username) {
	return $this->_userModel->findByLogin($username);
    }

}