<?php

class IMDT_Util_Ldap {

    private static $_instance;
    private $_connection;
    private $_isConnected;
    private $_settings;
    private $_allGroupsFilterLdapQuery;
    private $_allUsersFilterLdapQuery;

    public static function getInstance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public static function ldapNameToCleanName($entityCn) {
        return preg_replace('/CN=/i', '', $entityCn);
    }

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        if ($this->_isConnected == true) {
            return;
        }

        $this->_settings = IMDT_Service_Auth::getInstance()->getSettings();

        if ((!isset($this->_settings['auth-modes']['ldap'])) || ($this->_settings['auth-modes']['ldap'] == '0')) {
            throw new Exception('LDAP auth-mode not enabled');
        }

        $ldapSettings = $this->_settings['ldap'];

        $validSettingsForAdapter = array('host', 'port', 'base_dn', 'account_domain_name_short', 'account_canonical_form', 'username', 'password');
        $adapterSettings = array('server' => array());

        foreach ($validSettingsForAdapter as $validLdapSetting) {
            if (isset($ldapSettings[$validLdapSetting])) {
                $adapterSettings['server'][IMDT_Util_String::underscoreToCamelCase($validLdapSetting)] = $ldapSettings[$validLdapSetting];
            }
        }

        $this->_allGroupsFilterLdapQuery = (isset($ldapSettings['all_groups_filter_query']) ? $ldapSettings['all_groups_filter_query'] : null);
        $this->_allUsersFilterLdapQuery = (isset($ldapSettings['all_users_filter_query']) ? $ldapSettings['all_users_filter_query'] : null);

        $ldapAdapter = new Zend_Ldap($adapterSettings['server']);
        $ldapAdapter->connect();

        $this->_isConnected = true;
        $this->_connection = $ldapAdapter;
    }

    private function getConnection() {
        return $this->_connection;
    }

    private function getSettings() {
        return $this->_settings;
    }

    public function fetchAllUsers() {
        $userList = array();

        $memberCollection = $this->_connection->search($this->_allUsersFilterLdapQuery, null, Zend_Ldap::SEARCH_SCOPE_SUB, array('objectcategory', 'samaccountname', 'mail', 'displayname', 'memberof', 'objectClass'));
        $rMemberCollection = (($memberCollection instanceof Zend_Ldap_Collection) ? $memberCollection->toArray() : array());

        foreach ($rMemberCollection as $ldapMember) {
            $userList[$ldapMember['dn']] = $ldapMember;
        }

        return $userList;
    }

    public function fetchAllGroups() {
        $ldapGroups = $this->_connection->search($this->_allGroupsFilterLdapQuery, null, Zend_Ldap::SEARCH_SCOPE_SUB, array('cn', 'distinguishedName', 'memberOf', 'dn'));
        $rLdapGroups = $ldapGroups->toArray();
        $groupList = array();

        foreach ($rLdapGroups as $ldapGroup) {
            $groupList[trim($ldapGroup['dn'])] = $ldapGroup;
        }

        return $groupList;
    }

}
