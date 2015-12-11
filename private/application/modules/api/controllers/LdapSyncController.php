<?php
/**
  Keep local database in sync with LDAP entries
 *  */
class Api_LdapSyncController extends Zend_Rest_Controller {

    public function init() {
        ini_set('memory_limit', '512M');
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
            echo 'LDAP user count:' . count($ldapUserList) . '<br/>' . "\n";
            echo 'LDAP group count:' . count($ldapGroupList) . '<br/>' . "\n";
            echo 'DB user count:' . count($dbUserList) . '<br/>' . "\n";
            echo 'DB group count:' . count($dbGroupList) . '<br/>' . "\n";

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
                echo 'Groups to delete:' . count($groupsToDelete) . '<br/>' . "\n";

                if (count($groupsToDelete) > 0) {
                    $groupModel->delete('group_id in (' . join($groupsToDelete, ',') . ')');
                }
            }
            //Create groups that exists in LDAP and does not exists in DB
            if (true) {
                $groupsToInsert = array_diff_key($ldapGroupList, $dbGroupList);

                echo 'Groups to insert:' . count($groupsToInsert) . '<br/>' . "\n";

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

                echo 'Groups to compare:' . count($groupKeysToCompare) . '<br/>' . "\n";

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

                echo 'Updated groups:' . $updatedGroupsCount . '<br/>' . "\n";
            }
            //Update relations between groups
            if (true) {
                //Update group list in order to access the ID of the groups
                $dbGroupList = $this->getLdapGroupsFromDb();
                
                $groupGroupModel = new BBBManager_Model_GroupGroup();
                $selectRelations = $groupGroupModel->select('');
                $selectRelations->setIntegrityCheck(false);

                $selectRelations = $selectRelations->from(array('gg' => 'group_group'))
                        ->join(array('g' => 'group'), 'gg.group_id = g.group_id', 'g.internal_name as child_group')
                        ->join(array('parentg' => 'group'), 'gg.parent_group_id = parentg.group_id', 'parentg.internal_name as parent_group')
                        ->where(' g.auth_mode_id = ' . BBBManager_Config_Defines::$LDAP_AUTH_MODE . ' and parentg.auth_mode_id = ' . BBBManager_Config_Defines::$LDAP_AUTH_MODE);

                $arrayRelations = $groupGroupModel->fetchAll($selectRelations)->toArray();
                //array [ child ] [ parent ] = 1;
                $dbGroupMemberOfArray = array();
                foreach ($arrayRelations as $relation) {
                    $child_group = $relation ['child_group'];
                    $parent_group = $relation ['parent_group'];

                    if (!isset($dbGroupMemberOfArray[$child_group])) {
                        $dbGroupMemberOfArray[$child_group] = array();
                    }
                    $dbGroupMemberOfArray[$child_group][$parent_group] = $relation;
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
                
                if (false) {
                    echo 'DB Group Hierarchy: <br/> <pre>';
                    print_r($dbGroupMemberOfArray);
                    echo '</pre>';

                    echo 'LDAP Group Hierarchy: <br/> <pre>';
                    print_r($ldapGroupMemberOfArray);
                    echo '</pre>';
                }
                
                //Delete relations that exists in DB and does not exists in LDAP
                $deletedRelationsCount = 0;
                foreach($dbGroupMemberOfArray as $dbGroupInternalName => $dbGroupParents ) {
                    $ldapGroupParents = isset($ldapGroupMemberOfArray[$dbGroupInternalName])?$ldapGroupMemberOfArray[$dbGroupInternalName]:array();
                    
                    $relationsToDelete = array_diff_key($dbGroupParents, $ldapGroupParents);
                    
                    foreach ($relationsToDelete as $relationToDelete) {
                        $groupGroupModel->delete('group_id = ' .$relationToDelete['group_id']. ' and parent_group_id = ' . $relationToDelete['parent_group_id']);
                        $deletedRelationsCount++;
                    }
                }
                
                echo 'Removed relations:' . $deletedRelationsCount . '<br/>' . "\n";
                
                //Create relations that exists in LDAP and does not exists in DB
                $insertedRelationsCount = 0;
                foreach($ldapGroupMemberOfArray as $ldapGroupInternalName => $ldapGroupParents ) {
                    $dbGroupParents = isset($dbGroupMemberOfArray[$ldapGroupInternalName])?$dbGroupMemberOfArray[$ldapGroupInternalName]:array();
                    $relationsToInsert = array_diff_key($ldapGroupParents, $dbGroupParents);
                    
                    foreach ($relationsToInsert as $ldapParentGroupInternalName => $v) {
                        $childGroup = $dbGroupList[$ldapGroupInternalName]['group_id'];
                        $parentGroup = $dbGroupList[$ldapParentGroupInternalName]['group_id'];
                        
                        $insertData = array();
                        $insertData['group_id'] = $childGroup;
                        $insertData['parent_group_id'] = $parentGroup;
                        
                        $groupGroupModel->insert($insertData);
                        $insertedRelationsCount++;
                    }
                }
                echo 'Inserted relations:' . $insertedRelationsCount . '<br/>' . "\n";
            }
            
            //User sync code (not refactored)
            if (count($ldapUserList) > 0) {
                $userModel = new BBBManager_Model_User();
                echo '<hr/>';
                $ldapIndexedByLogin = array();
                foreach ($ldapUserList as $ldapUser) {
                    if(!isset($ldapUser['displayname'])) $ldapUser['displayname'] = array('Sem Nome');
                    $ldapUser['displayname'][0] = $ldapUser['displayname'][0];
                    $ldapIndexedByLogin[strtolower(current($ldapUser['samaccountname']))] = $ldapUser;
                }

                $rDeletes = array_diff_key($dbUserList, $ldapIndexedByLogin);
                echo 'Users to delete:' . count($rDeletes) . '<br/>' . "\n";
                $rInserts = array_diff_key($ldapIndexedByLogin, $dbUserList);
                echo 'Users to insert:' . count($rInserts) . '<br/>' . "\n";
                $rUpdates = array_intersect_key($dbUserList, $ldapIndexedByLogin);
                echo 'Users to compare:' . count($rUpdates) . '<br/>' . "\n";
                
                $rMustUpdate = array();

                $ldapSettings = IMDT_Util_Ldap::getInstance()->getSettings();
                $emailMapping = $ldapSettings['key_mapping']['email'];
                $fullNameMapping = $ldapSettings['key_mapping']['full_name'];
                $loginMapping = $ldapSettings['key_mapping']['login'];

                foreach ($rUpdates as $ldapMember => $ldapMemberInfo) {
                    $dataUpdate = array();

                    $dbFullName = $ldapMemberInfo['name'];
                    $dbLogin = $ldapMemberInfo['login'];
                    $dbEmail = $ldapMemberInfo['email'];
                    $dbDn = $ldapMemberInfo['ldap_cn'];

                    $ldapFullName = IMDT_Util_String::camelize(IMDT_Util_String::replaceTags($fullNameMapping, array('displayname' => current($ldapIndexedByLogin[$ldapMember]['displayname'])), true));
                    $ldapLogin = strtolower(IMDT_Util_String::replaceTags($loginMapping, array('samaccountname' => current($ldapIndexedByLogin[$ldapMember]['samaccountname']))));
                    $ldapEmail = (isset($ldapIndexedByLogin[$ldapMember]['email']) ? current($ldapIndexedByLogin[$ldapMember]['email']) : IMDT_Util_String::replaceTags($emailMapping, array('samaccountname' => $ldapLogin), true));
                    $ldapDn = $ldapIndexedByLogin[$ldapMember]['dn'];

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
                        //If user does not have a name, it's not imported
                        if(!isset($userToInsert['displayname']) || !is_array($userToInsert['displayname'])){
                            continue;
                        }

                        $rInsertData = array(
                            'auth_mode_id' => BBBManager_Config_Defines::$LDAP_AUTH_MODE,
                            'login' => $ldapLogin,
                            'name' => IMDT_Util_String::camelize(IMDT_Util_String::replaceTags($fullNameMapping, array('displayname' => current($userToInsert['displayname'])), true)),
                            'email' => (isset($userToInsert['email']) ? current($userToInsert['email']) : IMDT_Util_String::replaceTags($emailMapping, array('samaccountname' => $ldapLogin), true)),
                            'ldap_cn' => IMDT_Util_Ldap::ldapNameToCleanName($userToInsert['dn'])
                        );

                        $userModel->insert($rInsertData);
                    }
                } catch (Exception $ex) {
                    echo 'Erro: ' . $ex->getMessage();
                    die();
                    $adapter->rollBack();
                }
            }
            $adapter->commit();

            echo 'Users inactivated: ' . count($rDeletes) . '' . '<br/>' . "\n";
            echo "\n";
            echo 'Users updated: ' . count($rMustUpdate) . '<br/>' . "\n";
            echo "\n";
            echo 'Users inserted: ' . count($rInserts) . '<br/>' . "\n";
            
            
            
            //Update relations between users and groups
            if (true) {
                echo '<hr/>';
                //Update user list in order to access the ID of the users
                $dbUserList = $this->getLdapUsersFromDb();
                
                $userGroupModel = new BBBManager_Model_UserGroup();
                $selectRelations = $userGroupModel->select('');
                $selectRelations->setIntegrityCheck(false);

                $selectRelations = $selectRelations->from(array('ug' => 'user_group'))
                        ->join(array('g' => 'group'), 'ug.group_id = g.group_id', 'g.internal_name')
                        ->join(array('u' => 'user'),  'ug.user_id  = u.user_id' , 'u.login')
                        ->where(' g.auth_mode_id = ' . BBBManager_Config_Defines::$LDAP_AUTH_MODE . ' and u.auth_mode_id = ' . BBBManager_Config_Defines::$LDAP_AUTH_MODE);

                $arrayRelations = $userGroupModel->fetchAll($selectRelations)->toArray();
                
                //array [ user ] [ group ] = 1;
                $dbUserMemberOfArray = array();
                foreach ($arrayRelations as $relation) {
                    $user_login = $relation ['login'];
                    $group_internal_name = $relation ['internal_name'];

                    if (!isset($dbUserMemberOfArray[$user_login])) {
                        $dbUserMemberOfArray[$user_login] = array();
                    }
                    $dbUserMemberOfArray[$user_login][$group_internal_name] = $relation;
                }

                //Populate array with user and group hierarchy in LDAP
                //array [ user ] [ group ] = 1;
                $ldapUserMemberOfArray = array();

                foreach ($ldapUserList as $ldapUser) {
                    if(!isset($ldapUser['memberof']))
                        continue;
                    $ldapUserKey = strtolower(IMDT_Util_String::replaceTags($loginMapping, array('samaccountname' => current($ldapUser['samaccountname']))));

                    if (!isset($ldapUserMemberOfArray[$ldapUserKey]))
                        $ldapUserMemberOfArray[$ldapUserKey] = array();

                    foreach ($ldapUser['memberof'] as $ldapMemberOf) {
                        $ldapUserMemberOfArray[$ldapUserKey][$ldapMemberOf] = 1;
                    }
                }
                
                if (false) {
                    echo 'DB User Hierarchy: <br/> <pre>';
                    print_r($dbUserMemberOfArray);
                    echo '</pre>';

                    echo 'LDAP User Hierarchy: <br/> <pre>';
                    print_r($ldapUserMemberOfArray);
                    echo '</pre>';
                    die();
                }
                
                //Delete relations that exists in DB and does not exists in LDAP
                $deletedRelationsCount = 0;
                foreach($dbUserMemberOfArray as $dbUserInternalName => $dbUserGroups ) {
                    $ldapUserGroups = isset($ldapUserMemberOfArray[$dbUserInternalName])?$ldapUserMemberOfArray[$dbUserInternalName]:array();
                    
                    $relationsToDelete = array_diff_key($dbUserGroups, $ldapUserGroups);
                    
                    foreach ($relationsToDelete as $relationToDelete) {
                        $userGroupModel->delete('group_id = ' .$relationToDelete['group_id']. ' and user_id = ' . $relationToDelete['user_id']);
                        $deletedRelationsCount++;
                    }
                }
                
                echo 'Removed relations:' . $deletedRelationsCount . '<br/>' . "\n";
                
                //Create relations that exists in LDAP and does not exists in DB
                $insertedRelationsCount = 0;
                foreach($ldapUserMemberOfArray as $ldapUserKey => $ldapUserGroups ) {
                    $dbUserGroups = isset($dbUserMemberOfArray[$ldapUserKey])?$dbUserMemberOfArray[$ldapUserKey]:array();
                    $relationsToInsert = array_diff_key($ldapUserGroups, $dbUserGroups);
                    
                    foreach ($relationsToInsert as $ldapGroupInternalName => $v) {
                        $group_id = $dbGroupList[$ldapGroupInternalName]['group_id'];
                        $user_id = $dbUserList[$ldapUserKey]['user_id'];
                        
                        $insertData = array();
                        $insertData['group_id'] = $group_id;
                        $insertData['user_id']  = $user_id;
                        
                        $userGroupModel->insert($insertData);
                        $insertedRelationsCount++;
                    }
                }
                echo 'Inserted relations:' . $insertedRelationsCount . '<br/>' . "\n";
            }
            BBBManager_Util_AccessProfileChanges::getInstance()->mustChange();
        } catch (Exception $ex) {
            echo 'Erro: ' . $ex->getMessage();
            die();
        }
        
        
        die();
    }

    public function postAction() {
        
    }

    public function putAction() {
        
    }

}
