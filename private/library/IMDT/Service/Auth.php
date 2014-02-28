<?php

class IMDT_Service_Auth {

    private static $_instance;
    private $_settings;
    private $_authResult;
    private $_authAdapters;
    private $_authAdaptersPriority;

    public static function getInstance() {
	if (self::$_instance == null) {
	    self::$_instance = new IMDT_Service_Auth();
	}

	return self::$_instance;
    }

    public function __construct() {
	$this->_settings = BBBManager_Cache_Auth::getInstance()->getData();

	$this->getAvailableAdapters();
	$this->getAdaptersPriority();
    }

    public function authenticate($username, $password) {
	if ($this->_checkAvailableAdapters() == false) {
	    throw new Exception('No authentication adapter found, please check auth-modes setting in ' . BBBManager_Cache_Auth::getInstance()->getIniFilePath() . ' file.');
	}
	
	$successfulAuthentication = false;

	foreach ($this->_authAdaptersPriority as $authAdapterKey) {
	    if ($this->isAdapterAvailable($authAdapterKey)) {
		$authAdapter = $this->_getAdapter($authAdapterKey);
		if ($authAdapter == null) {
		    die('tratar aqui');
		}
		$authAdapter->setSettings($this->_settings);

		$this->_authResult = $authAdapter->authenticate($username, $password);

		$successfulAuthentication = $this->_authResult->isValid();

		if (!$this->_authResult->getTryNextAuthMode())
		    break;
	    }

	    if ($successfulAuthentication == true) {
		break;
	    }
	}

	if ($successfulAuthentication) {
	    $this->afterSuccessfulAuthentication();
	}

	return $successfulAuthentication;
    }

    public function afterSuccessfulAuthentication() {
	$authData = $this->_authResult->getAuthData();
	$userId = (isset($authData['id']) ? $authData['id'] : null);

	if ($userId == null) {
	    throw new Exception(IMDT_Util_Translate::_('Successful authentication, but invalid user id') . '.');
	}
        
	
        if($authData['auth_mode'] == BBBManager_Config_Defines::$LDAP_AUTH_MODE){
            BBBManager_Cache_GroupSync::getInstance()->getData();
        }
        
	BBBManager_Cache_GroupHierarchy::getInstance()->getData();
	BBBManager_Cache_GroupsAccessProfile::getInstance()->getData();


	$userGroupModel = new BBBManager_Model_UserGroup();
	$userGroups = $userGroupModel->findByUserId($userId);
	$authData['userGroups'] = ($userGroups != null ? $userGroups->toArray() : array());

	$allGroups = array();

	foreach ($authData['userGroups'] as $group) {
	    if (!isset($allGroups[$group['group_id']])) {
		$allGroups[$group['group_id']] = $group;
	    }
	}

	if (isset($authData['localGroupsFromLdapGroups'])) {
	    foreach ($authData['localGroupsFromLdapGroups'] as $group) {
		if (!isset($allGroups[$group['group_id']])) {
		    $allGroups[$group['group_id']] = $group;
		}
	    }
	}

	if (isset($authData['ldapGroupsInDatabase'])) {
	    foreach ($authData['ldapGroupsInDatabase'] as $group) {
		if (!isset($allGroups[$group['group_id']])) {
		    $allGroups[$group['group_id']] = $group;
		}
	    }
	}
	
	$allGroupsWithHierarchy = array();
	$groupHierarchy = BBBManager_Cache_GroupHierarchy::getInstance()->getData();
	
	foreach($allGroups as $groupId => $group){
	    $allGroupsWithHierarchy[$groupId] = $group;
	    
	    if(isset($groupHierarchy[$groupId]) && isset($groupHierarchy[$groupId]['parents'])){
		foreach($groupHierarchy[$groupId]['parents'] as $parentGroup){
		    $allGroupsWithHierarchy[$parentGroup['group_id']] = $parentGroup;
		}
	    }
	}

	$authData['groups'] = $allGroupsWithHierarchy;
	$authData['groupIds'] = array_keys($allGroupsWithHierarchy);

	IMDT_Util_AccessProfile::generate($authData);

	$authData['token'] = session_id();

	$this->_authResult->setAuthData($authData);
    }

    private function _checkAvailableAdapters() {
	$active = false;

	foreach ($this->_authAdapters as $adapter => $activeAdapter) {
	    $active = $active || $activeAdapter;
	}

	return $active;
    }

    public function isAdapterAvailable($adapterKey) {
	return (isset($this->_authAdapters[$adapterKey]) ? $this->_authAdapters[$adapterKey] : false);
    }

    public function getAvailableAdapters() {
	$authModes = (isset($this->_settings['auth-modes']) ? $this->_settings['auth-modes'] : null);

	if ($authModes == null || (!is_array($authModes))) {
	    throw new Exception('Expected setting auth-modes not found in ' . BBBManager_Cache_Auth::getInstance()->getIniFilePath() . ' file.');
	}

	foreach ($authModes as $adapter => $activeAdapter) {
	    if (!is_array($adapter)) {
		$this->_authAdapters[$adapter] = $activeAdapter;
	    }
	}
    }

    public function getAdaptersPriority() {
	$authAdaptersPriority = (isset($this->_settings['auth-modes']['priority']) ? $this->_settings['auth-modes']['priority'] : null);

	if ($authAdaptersPriority == null || (!is_array($authAdaptersPriority))) {
	    throw new Exception('Expected setting auth-modes.priority not found in ' . BBBManager_Cache_Auth::getInstance()->getIniFilePath() . ' file.');
	}

	foreach ($authAdaptersPriority as $authAdapter) {
	    $this->_authAdaptersPriority[] = $authAdapter;
	}
    }

    public function getSettings() {
	return $this->_settings;
    }

    public function setSettings($settings) {
	$this->_settings = $settings;
    }

    private function _getAdapter($authAdapter) {
	$adapterClass = 'IMDT_Service_Auth_Adapter_' . ucwords($authAdapter);

	if (class_exists($adapterClass) == false) {
	    throw new Exception($adapterClass . ' not found.');
	}

	return new $adapterClass();
    }

    public function getAuthResult() {
	return $this->_authResult;
    }

    public function setAuthResult($authResult) {
	$this->_authResult = $authResult;
    }

}