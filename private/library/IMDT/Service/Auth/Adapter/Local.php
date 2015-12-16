<?php

class IMDT_Service_Auth_Adapter_Local {

    private $_settings;

    public function authenticate($username, $password, $moodle = false) {
        if ($this->_settings == null) {
            $this->_settings = IMDT_Service_Auth::getInstance()->getSettings();
        }

        if ((!isset($this->_settings['database']['table_name'])) || (!isset($this->_settings['database']['user_name_column'])) || (!isset($this->_settings['database']['password_column']))) {
            throw new Exception(__CLASS__ . ', invalid parameters, you must define parameters: table_name, user_name_column and password_column in auth.ini file.');
        }

        /* $zfAuthAdapter = new Zend_Auth_Adapter_DbTable();
          $zfAuthAdapter->setTableName($this->_settings['database']['table_name']);
          $zfAuthAdapter->setIdentityColumn($this->_settings['database']['user_name_column']);
          $zfAuthAdapter->setCredentialColumn($this->_settings['database']['password_column']);

          $zfAuthAdapter->setIdentity($username);
          $zfAuthAdapter->setCredential($password); */

        $modelUser = new BBBManager_Model_User();

        $dbAdapter = Zend_Db_Table::getDefaultAdapter();

        $authSelect = $dbAdapter->select();
        $authSelect->from(
                $this->_settings['database']['table_name'], array(
            '*',
            'authok' => new Zend_Db_Expr(
                    'CASE
                            WHEN 1 = ' . ($moodle == true ? '1' : '0') . '
                                THEN 1
                            WHEN ' . $dbAdapter->quoteInto('password = ?', IMDT_Util_Hash::generate($password)) . '
                                THEN 1
                            ELSE
                                0
                         END'
            ),
            'valid' => new Zend_Db_Expr($modelUser->getSqlStatementForActiveUsers())
                )
        );

        $authSelect->where('login = ?', $username);
        $authSelect->where('auth_mode_id = ?', BBBManager_Config_Defines::$LOCAL_AUTH_MODE);

        $dbRecord = $dbAdapter->fetchRow($authSelect);

        if ($dbRecord != null && $dbRecord['authok'] == 1 && $dbRecord['valid'] == '0') {
            throw new Exception(IMDT_Util_Translate::_('Your user is blocked: validity period has expired.'));
        }

        if ($dbRecord != null && $dbRecord['authok'] == 1) {
            $authResult = new IMDT_Service_Auth_Result(new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $username));
        } elseif ($dbRecord != null && $dbRecord['authok'] == 0) {
            $authResult = new IMDT_Service_Auth_Result(new Zend_Auth_Result(Zend_Auth_Result::FAILURE, $username));
        } else {
            $authResult = new IMDT_Service_Auth_Result(new Zend_Auth_Result(Zend_Auth_Result::FAILURE, $username), true);
        }

        $userInfo = array();

        if (isset($this->_settings['database']['key_mapping']) && (is_array($this->_settings['database']['key_mapping']))) {
            foreach ($this->_settings['database']['key_mapping'] as $key => $value) {

                if (isset($dbRecord[$value])) {
                    $userInfo[$key] = $dbRecord[$value];
                }
            }

            $authResult->setAuthData($userInfo);
        }
        
        if($authResult->isValid()) {
            if($authResult->getAuthData()['access_profile_id'] == null) {
                throw new Exception(IMDT_Util_Translate::_('User logged in but no access profile detected.'));
            }
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

}
