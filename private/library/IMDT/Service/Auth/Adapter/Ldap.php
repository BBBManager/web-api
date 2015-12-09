<?php

class IMDT_Service_Auth_Adapter_Ldap {

    private $_settings;
    private $_userModel;
    private $_groupModel;

    public function __construct() {
        $this->_userModel = new BBBManager_Model_User();
        $this->_groupModel = new BBBManager_Model_Group();
    }

    public function authenticate($username, $password, $moodle = false) {
        if ($this->_settings == null) {
            $this->_settings = IMDT_Service_Auth::getInstance()->getSettings();
        }

        if ((!isset($this->_settings['ldap']['host'])) || (!isset($this->_settings['ldap']['base_dn'])) || (!isset($this->_settings['ldap']['username'])) || (!isset($this->_settings['ldap']['password']))) {
            throw new Exception(__CLASS__ . ', invalid parameters, you must define parameters: host, base_dn, username and password in auth.ini file.');
        }

        $ldapAdapterConfig = array('server' => array());

        foreach ($this->_settings['ldap'] as $key => $value) {
            if (in_array($key, array('key_mapping', 'all_groups_filter_query', 'all_users_filter_query'))) {
                continue;
            }
            $ldapAdapterConfig['server'][IMDT_Util_String::underscoreToCamelCase($key)] = $value;
        }

        $ldapConnection = new Zend_Ldap($ldapAdapterConfig['server']);
        $ldapConnection->connect();
        $ldapResult = $ldapConnection->search('(sAMAccountName=' . $username . ')');
        $userDn = $ldapResult->dn();

        $authResultObject = new stdClass();

        foreach ($ldapResult->getFirst() as $k => $v) {
            if (is_array($v)) {
                $authResultObject->$k = current($v);
            } else {
                $authResultObject->$k = $v;
            }
        }

        if ($moodle == false) {
            $zfAuthAdapter = new Zend_Auth_Adapter_Ldap($ldapAdapterConfig, $userDn, $password);
            $zfAuthResult = Zend_Auth::getInstance()->authenticate($zfAuthAdapter);
            $authResult = new IMDT_Service_Auth_Result($zfAuthResult);
        } else {
            $authResult = new IMDT_Service_Auth_Result(new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $username));
        }

        if ($authResult->isValid()) {
            if (isset($this->_settings['ldap']['key_mapping']) && (is_array($this->_settings['ldap']['key_mapping']))) {
                $authResultArray = array();

                foreach ($authResultObject as $k => $v) {
                    $authResultArray[$k] = $v;
                }

                foreach ($this->_settings['ldap']['key_mapping'] as $key => $value) {
                    $userInfo[$key] = IMDT_Util_String::replaceTags($value, $authResultArray, true);

                    if ($key == 'full_name') {
                        $userInfo[$key] = IMDT_Util_String::camelize($userInfo[$key]);
                    }
                }
            } else {
                foreach ($authResultObject as $k => $v) {
                    $userInfo[$k] = $v;
                }
            }

            $isUserInDatabase = $this->_checkLocalUser($username);
            $userDbData = $isUserInDatabase->toArray();

            $userInfo['id'] = $userDbData['user_id'];
            $userInfo['auth_mode'] = $userDbData['auth_mode_id'];
            $userInfo['access_profile_id'] = $userDbData['access_profile_id'];

            if($userDbData['access_profile_id'] == null) {
                throw new Exception(IMDT_Util_Translate::_('User logged in but no access profile detected.'));
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
