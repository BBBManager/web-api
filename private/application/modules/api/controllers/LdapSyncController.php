<?php

/**
  Keep local database in sync with LDAP entries
 *  */
class Api_LdapSyncController extends Zend_Rest_Controller {

    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
    }

    public function getAction() {
        
    }

    public function deleteAction() {
        
    }

    public function headAction() {
        
    }

    private function getLdapUsersFromDb() {
        $userModel = new BBBManager_Model_User();
        $findLdapUsers = $userModel->select();
        $findLdapUsers->where('auth_mode_id = ?', BBBManager_Config_Defines::$LDAP_AUTH_MODE);

        $dbLdapUsers = $userModel->fetchAll($findLdapUsers);
        $dbLdapUsers = (($dbLdapUsers instanceof Zend_Db_Table_Rowset) ? $dbLdapUsers->toArray() : array());
        $rDbLdapUsers = array();

        if (count($dbLdapUsers) > 0) {
            foreach ($dbLdapUsers as $dbLdapUser) {
                //$rDbLdapUsers[strtolower(IMDT_Util_Ldap::ldapNameToCleanName($dbLdapUser['ldap_cn']))] = $dbLdapUser;
                $rDbLdapUsers[strtolower($dbLdapUser['login'])] = $dbLdapUser;
            }
        }

        return $rDbLdapUsers;
    }

    private function getLdapGroupsFromDb() {
        $groupModel = new BBBManager_Model_Group();
        $findLdapGroups = $groupModel->select();
        $findLdapGroups->where('auth_mode_id = ?', BBBManager_Config_Defines::$LDAP_AUTH_MODE);

        $dbLdapGroups = $groupModel->fetchAll($findLdapGroups);
        $dbLdapGroups = (($dbLdapGroups instanceof Zend_Db_Table_Rowset) ? $dbLdapGroups->toArray() : array());
        $rDbLdapGroups = array();

        if (count($dbLdapGroups) > 0) {
            foreach ($dbLdapGroups as $dbLdapGroup) {
                $rDbLdapGroups[$dbLdapGroup['internal_name']] = $dbLdapGroup;
            }
        }

        return $rDbLdapGroups;
    }

    public function indexAction() {
        try {
            $ldapUserList = IMDT_Util_Ldap::getInstance()->fetchAllUsers();
            $ldapGroupList = IMDT_Util_Ldap::getInstance()->fetchAllGroups();

            if (count($ldapUserList) == 0 || count($ldapGroupList) == 0) {
                echo 'Not processing due to empty response of LDAP server.';
                die();
            }

            $dbUserList = $this->getLdapUsersFromDb();
            $dbGroupList = $this->getLdapGroupsFromDb();

            echo 'Summary:<br/>';
            echo 'LDAP user count:' . count($ldapUserList) . '<br/>';
            echo 'LDAP group count:' . count($ldapGroupList) . '<br/>';
            echo 'DB user count:' . count($dbUserList) . '<br/>';
            echo 'DB group count:' . count($dbGroupList) . '<br/>';

            echo '<hr/>';
            $groupModel = new BBBManager_Model_Group();
            //Delete groups that exists in DB and does not exists in LDAP
            if (true) {
                $groupsToDelete = array();

                if (false) {
                    echo 'DB Group Keys: <br/> <pre>';
                    print_r(array_keys($dbGroupList));
                    echo '</pre>';
                    echo 'LDAP Group Keys: <br/> <pre>';
                    print_r(array_keys($ldapGroupList));
                    echo '</pre>';
                }
                foreach (array_diff_key($dbGroupList, $ldapGroupList) as $groupToDelete) {
                    $groupsToDelete[] = $groupToDelete['group_id'];
                }
                echo 'Groups to delete:' . count($groupsToDelete) . '<br/>';

                if (count($groupsToDelete) > 0) {
                    $groupModel->delete('group_id in (' . join($groupsToDelete, ',') . ')');
                }
            }
            //Create groups that exists in LDAP and does not exists in DB
            if (true) {
                $groupsToInsert = array_diff_key($ldapGroupList, $dbGroupList);

                echo 'Groups to insert:' . count($groupsToInsert) . '<br/>';

                foreach ($groupsToInsert as $groupToInsert) {
                    $rInsertData = array(
                        'auth_mode_id' => BBBManager_Config_Defines::$LDAP_AUTH_MODE,
                        'name' => $groupToInsert['cn'][0],
                        'internal_name' => trim($groupToInsert['dn'])
                    );

                    $groupModel->insert($rInsertData);
                }
            }
            //Update groups that exists in LDAP and in DB
            if (true) {
                $groupKeysToCompare = array_keys(array_intersect_key($ldapGroupList, $dbGroupList));

                echo 'Groups to compare:' . count($groupKeysToCompare) . '<br/>';

                $updatedGroupsCount = 0;

                foreach ($groupKeysToCompare as $groupKeyToCompare) {
                    $dbGroup = $dbGroupList[$groupKeyToCompare];
                    $ldapGroup = $ldapGroupList[$groupKeyToCompare];

                    $updateData = array();

                    //Compare name of group
                    $ldapGroupName = $ldapGroup['cn'][0];
                    if ($ldapGroupName != $dbGroup['name']) {
                        $updateData['name'] = $ldapGroupName;
                    }

                    if (count($updateData) > 0) {
                        $updatedGroupsCount++;
                        $groupModel->update($updateData, $groupModel->getAdapter()->quoteInto('group_id = ?', $dbGroup['group_id']));
                    }
                }

                echo 'Updated groups:' . $updatedGroupsCount . '<br/>';
            }
            //Update relations between groups
            if (true) {
                $groupGroupModel = new BBBManager_Model_GroupGroup();
                $selectRelations = $groupGroupModel->select('');
                $selectRelations->setIntegrityCheck(false);

                $selectRelations = $selectRelations->from(array('gg' => 'group_group'))
                        ->join(array('g' => 'group'), 'gg.group_id = g.group_id', 'g.internal_name as child_group')
                        ->join(array('parentg' => 'group'), 'gg.parent_group_id = parentg.group_id', 'parentg.internal_name as parent_group')
                        ->where(' g.auth_mode_id = ' . BBBManager_Config_Defines::$LDAP_AUTH_MODE . ' and parentg.auth_mode_id = ' . BBBManager_Config_Defines::$LDAP_AUTH_MODE);

                $arrayRelations = $groupGroupModel->fetchAll($selectRelations)->toArray();

                //Populate array with group hierarchy in DB
                //array [ child ] [ parent ] = 1;
                $dbGroupMemberOfArray = array();
                foreach ($arrayRelations as $relation) {
                    $child_group = $relation ['child_group'];
                    $parent_group = $relation ['parent_group'];

                    if (!isset($dbGroupMemberOfArray[$child_group])) {
                        $dbGroupMemberOfArray[$child_group] = array();
                    }
                    $dbGroupMemberOfArray[$child_group][$parent_group] = 1;
                }

                //Populate array with group hierarchy in LDAP
                //array [ child ] [ parent ] = 1;
                $ldapGroupMemberOfArray = array();

                foreach ($ldapGroupList as $ldapGroup) {
                    
                    if(!isset($ldapGroup['memberof']))
                        continue;

                    if (!isset($ldapGroupMemberOfArray[$ldapGroup['dn']]))
                        $ldapGroupMemberOfArray[$ldapGroup['dn']] = array();

                    foreach ($ldapGroup['memberof'] as $ldapMemberOf) {
                        $ldapGroupMemberOfArray[$ldapGroup['dn']][$ldapMemberOf] = 1;
                    }
                }
                
                if (true) {
                    echo 'DB Group Hierarchy: <br/> <pre>';
                    print_r($dbGroupMemberOfArray);
                    echo '</pre>';

                    echo 'LDAP Group Hierarchy: <br/> <pre>';
                    print_r($ldapGroupMemberOfArray);
                    echo '</pre>';
                }
            }

            die();
            if (count($ldapMembersList) > 0) {
                $rLdapMembersList = array();
                foreach ($ldapMembersList as $ldapMember => $ldapMemberInfo) {
                    //$rLdapMembersList[strtolower(IMDT_Util_Ldap::ldapNameToCleanName($ldapMember))] = $ldapMemberInfo;
                    $rLdapMembersList[strtolower(current($ldapMemberInfo['samaccountname']))] = $ldapMemberInfo;
                }

                $rDeletes = array_diff_key($rDbLdapUsers, $rLdapMembersList);
                $rInserts = array_diff_key($rLdapMembersList, $rDbLdapUsers);
                $rUpdates = array_intersect_key($rDbLdapUsers, $rLdapMembersList);
                $rMustUpdate = array();

                $emailMapping = $ldapSettings['ldap']['key_mapping']['email'];
                $fullNameMapping = $ldapSettings['ldap']['key_mapping']['full_name'];
                $loginMapping = $ldapSettings['ldap']['key_mapping']['login'];

                foreach ($rUpdates as $ldapMember => $ldapMemberInfo) {
                    $dataUpdate = array();

                    $dbFullName = $ldapMemberInfo['name'];
                    $dbLogin = $ldapMemberInfo['login'];
                    $dbEmail = $ldapMemberInfo['email'];
                    $dbDn = $ldapMemberInfo['ldap_cn'];

                    $ldapFullName = IMDT_Util_String::camelize(IMDT_Util_String::replaceTags($fullNameMapping, array('displayname' => current($rLdapMembersList[$ldapMember]['displayname'])), true));
                    $ldapLogin = strtolower(IMDT_Util_String::replaceTags($loginMapping, array('samaccountname' => current($rLdapMembersList[$ldapMember]['samaccountname']))));
                    $ldapEmail = (isset($rLdapMembersList[$ldapMember]['email']) ? current($rLdapMembersList[$ldapMember]['email']) : IMDT_Util_String::replaceTags($emailMapping, array('samaccountname' => $ldapLogin), true));
                    $ldapDn = $rLdapMembersList[$ldapMember]['dn'];

                    if ($dbFullName != $ldapFullName) {
                        $dataUpdate['name'] = $ldapFullName;
                    }

                    if ($dbEmail != $ldapEmail) {
                        $dataUpdate['email'] = $ldapEmail;
                    }

                    if ($dbLogin != $ldapLogin) {
                        $dataUpdate['login'] = $ldapLogin;
                    }

                    if ($ldapDn != $dbDn) {
                        $dataUpdate['ldap_cn'] = IMDT_Util_Ldap::ldapNameToCleanName($ldapDn);
                    }

                    if (count($dataUpdate) > 0) {
                        $rMustUpdate[$ldapMemberInfo['user_id']] = $dataUpdate;
                    }
                }

                $adapter = $userModel->getAdapter();
                $adapter->beginTransaction();

                try {
                    foreach ($rDeletes as $userToDelete) {
                        $whereInactivate = $adapter->quoteInto('user_id = ?', $userToDelete['user_id']);
                        $userModel->update(array('valid_to' => date('Y-m-d', strtotime('now - 1 day'))), $whereInactivate);
                    }

                    foreach ($rMustUpdate as $userId => $updateData) {
                        $whereUpdate = $adapter->quoteInto('user_id = ?', $userId);
                        $userModel->update($updateData, $whereUpdate);
                    }

                    foreach ($rInserts as $userToInsert) {
                        $ldapLogin = strtolower(IMDT_Util_String::replaceTags($loginMapping, array('samaccountname' => current($userToInsert['samaccountname']))));

                        $rInsertData = array(
                            'auth_mode_id' => BBBManager_Config_Defines::$LDAP_AUTH_MODE,
                            'login' => $ldapLogin,
                            'name' => IMDT_Util_String::camelize(IMDT_Util_String::replaceTags($fullNameMapping, array('displayname' => current($userToInsert['displayname'])), true)),
                            'email' => (isset($userToInsert['email']) ? current($userToInsert['email']) : IMDT_Util_String::replaceTags($emailMapping, array('samaccountname' => $ldapLogin), true)),
                            'ldap_cn' => IMDT_Util_Ldap::ldapNameToCleanName($userToInsert['dn']),
                            'access_profile_id' => BBBManager_Config_Defines::$SYSTEM_USER_PROFILE
                        );

                        $userModel->insert($rInsertData);
                    }
                } catch (Exception $ex) {
                    $adapter->rollBack();
                }
            }
            $adapter->commit();

            echo "\n";
            echo "\n";
            echo 'LDAP sync';
            echo "\n";
            echo '---------';
            echo "\n";
            echo count($rDeletes) . ' users inactivated';
            echo "\n";
            echo count($rMustUpdate) . ' users updated';
            echo "\n";
            echo count($rInserts) . ' users inserted';
        } catch (Exception $ex) {
            $this->view->response = array(
                'success' => '0',
                'msg' => $ex->getMessage()
            );
        }
    }

    public function postAction() {
        
    }

    public function putAction() {
        
    }

}
