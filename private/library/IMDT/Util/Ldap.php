<?php

class IMDT_Util_Ldap {

    private static $_instance;
    private $_connection;
    private $_isConnected;
    private $_settings;
    private $_groupsFromUserLdapQuery;
    private $_usersFromGroupLdapQuery;
    private $_allGroupsFilterLdapQuery;


    public static function getInstance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }

        return self::$_instance;
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

        $this->_groupsFromUserLdapQuery = (isset($ldapSettings['groups_from_user_query']) ? $ldapSettings['groups_from_user_query'] : null);
        $this->_usersFromGroupLdapQuery = (isset($ldapSettings['users_from_group_query']) ? $ldapSettings['users_from_group_query'] : null);
        $this->_allGroupsFilterLdapQuery = (isset($ldapSettings['all_groups_filter_query']) ? $ldapSettings['all_groups_filter_query'] : null);

        $ldapAdapter = new Zend_Ldap($adapterSettings['server']);
        $ldapAdapter->connect();

        $this->_isConnected = true;
        $this->_connection = $ldapAdapter;
    }

    public function fetchAllGroups() {
        //$allGroupsQuery = '(&(objectClass=group)(!(isCriticalSystemObject=true)))';
        //$allGroupsQuery = '(&(objectclass=group)(|(cn=gg_ceaf*)(cn=gg_servidor)))';

        $ldapGroups = $this->_connection->search($this->_allGroupsFilterLdapQuery);
        $rLdapGroups = $ldapGroups->toArray();

        $groups = array();
        $groupsMemberOfMapping = array();

        foreach ($rLdapGroups as $ldapGroup) {
            /* if(stripos($ldapGroup['dn'],'webconf') !== false){
              echo '<pre>';
              var_dump($ldapGroup);die;
              } */
            $groups[] = array('name' => self::ldapNameToCleanName($ldapGroup['dn']), 'visible' => true);

            if (isset($ldapGroup['memberof'])) {
                if (is_array($ldapGroup['memberof'])) {
                    foreach ($ldapGroup['memberof'] as $memberOfItem) {
                        $groupsMemberOfMapping[self::ldapNameToCleanName($ldapGroup['dn'])][] = self::ldapNameToCleanName($memberOfItem);
                        $groups[] = array('name' => self::ldapNameToCleanName($memberOfItem), 'visible' => false);
                    }
                } else {
                    $groupsMemberOfMapping[self::ldapNameToCleanName($ldapGroup['dn'])][] = self::ldapNameToCleanName($ldapGroup['memberof']);
                    $groups[] = array('name' => self::ldapNameToCleanName($ldapGroup['memberof']), 'visible' => false);
                }
            }

            if (isset($ldapGroup['member'])) {
                if (is_array($ldapGroup['member'])) {
                    foreach ($ldapGroup['member'] as $memberOfItem) {
                        if (stripos($memberOfItem, 'gg_') === false) {
                            continue;
                        }

                        if (!isset($groupsMemberOfMapping[self::ldapNameToCleanName($memberOfItem)])) {
                            $groupsMemberOfMapping[self::ldapNameToCleanName($memberOfItem)] = array();
                        }

                        $groupsMemberOfMapping[self::ldapNameToCleanName($memberOfItem)][] = self::ldapNameToCleanName($ldapGroup['dn']);
                        //$groupsMemberOfMapping[self::ldapNameToCleanName($ldapGroup['dn'])][] = self::ldapNameToCleanName($memberOfItem);
                        $groups[] = array('name' => self::ldapNameToCleanName($memberOfItem), 'visible' => false);
                    }
                } else {
                    if (stripos($ldapGroup['member'], 'gg_') !== false) {
                        //$groupsMemberOfMapping[self::ldapNameToCleanName($ldapGroup['dn'])][] = self::ldapNameToCleanName($ldapGroup['member']);
                        if (!isset($groupsMemberOfMapping[self::ldapNameToCleanName($ldapGroup['member'])])) {
                            $groupsMemberOfMapping[self::ldapNameToCleanName($ldapGroup['member'])] = array();
                        }

                        $groupsMemberOfMapping[self::ldapNameToCleanName($ldapGroup['member'])][] = self::ldapNameToCleanName($ldapGroup['dn']);

                        $groups[] = array('name' => self::ldapNameToCleanName($ldapGroup['member']), 'visible' => false);
                    }
                }
            }
        }

        return array(
            'groups' => $groups,
            'groupsMemberOfMapping' => $groupsMemberOfMapping
        );
    }

    public function fetchUsersFromGroup($groupCn) {
        $usersFromGroupQuery = IMDT_Util_String::replaceTags($this->_usersFromGroupLdapQuery, array('group' => $groupCn));
        $ldapResult = $this->_connection->search($usersFromGroupQuery);

        $rUsersFromGroup = $ldapResult->toArray();

        $usersNames = array();

        foreach ($rUsersFromGroup as $user) {
            $usersNames[] = $user['cn'][0];
        }

        return $usersNames;
    }

    public function fetchGroupsFromUser($userCn) {
        $groupsFromUserQuery = IMDT_Util_String::replaceTags($this->_groupsFromUserLdapQuery, array('user' => $userCn));
        $ldapResult = $this->_connection->search($groupsFromUserQuery);

        $rGroupsFromUser = $ldapResult->toArray();

        $groupsNames = array();

        foreach ($rGroupsFromUser as $group) {
            $groupsNames[] = self::ldapNameToCleanName($group['dn']);
        }

        return $groupsNames;
    }

    /*
     * 	Removes CN and the base dn from an entity cn
     */

    public static function ldapNameToCleanName($entityCn) {
        return preg_replace('/CN=/i', '', $entityCn);
    }

    public function getConnection() {
        return $this->_connection;
    }

    public function getSettings() {
        return $this->_settings;
    }

    public function findMembersRecursively(&$membersList, $ldapFilter = null) {

        if ($ldapFilter == null) {
            $ldapFilter = $this->_settings['ldap']['users_sync_query'];
        }

        $memberCollection = $this->_connection->search($ldapFilter, null, Zend_Ldap::SEARCH_SCOPE_SUB, array('objectcategory', 'samaccountname', 'mail', 'displayname', 'memberof'));
        $rMemberCollection = (($memberCollection instanceof Zend_Ldap_Collection) ? $memberCollection->toArray() : array());

        foreach ($rMemberCollection as $ldapMember) {
            $objectCategory = current($ldapMember['objectcategory']);
            if (stripos($objectCategory, 'group') !== false) {
                $ldapQuery = '(&(!(userAccountControl:1.2.840.113556.1.4.803:=2))(!(samaccountname=$*))(memberOf=' . $ldapMember['dn'] . '))';
                IMDT_Util_Ldap::getInstance()->findMembersRecursively($membersList, $ldapQuery);
            } else {
                $membersList[$ldapMember['dn']] = $ldapMember;
            }
        }
    }

}
