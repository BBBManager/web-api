<?php

class Api_AccessProfilesUpdateController extends Zend_Rest_Controller {
    public function init() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout()->disableLayout();
    }

    public function deleteAction() {
    }

    public function getAction() {
        try{
            $pUuid = $this->_getParam('r');
            
            if(file_exists(BBBManager_Util_AccessProfileChanges::getInstance()->getProgressFileName($pUuid))){
                $countJson = file_get_contents(BBBManager_Util_AccessProfileChanges::getInstance()->getProgressFileName($pUuid));
                $objCount = Zend_Json::decode($countJson);
                
                if($objCount['doneCount'] > 0){
                    $donePerc = (($objCount['doneCount'] * 100) / $objCount['totalCount']);    
                }else{
                    $donePerc = 1;
                }

                $this->view->response = array(
                    'success'   => '1',
                    'perc'      => $donePerc,
                    'total'     => $objCount['totalCount'],
                    'done'      => $objCount['doneCount']
                );

                if($donePerc == 100){
                    unlink(BBBManager_Util_AccessProfileChanges::getInstance()->getProgressFileName($pUuid));
                    BBBManager_Util_AccessProfileChanges::getInstance()->cleanProgressFiles();
                }
            }else{
                throw new Exception('Error');
            }
        } catch (Exception $ex) {
            $this->view->response = array(
                'success'   => '0',
                'msg'       => $ex->getMessage()
            );
        }
    }

    public function headAction() {
    }

    public function indexAction() {
        $this->view->response = array(
            'success'       => '1', 
            'mustValidate'  => (file_exists(BBBManager_Util_AccessProfileChanges::getInstance()->getFileName())) ? '1' : '0'
        );
    }

    public function postAction() {
	
    }

    public function putAction() {
        BBBManager_Cache_GroupSync::getInstance()->clean();
        BBBManager_Cache_GroupHierarchy::getInstance()->clean();
        BBBManager_Cache_GroupsAccessProfile::getInstance()->clean();
        BBBManager_Cache_GroupsAccessProfile::getInstance()->clean();
        
        BBBManager_Cache_GroupSync::getInstance()->getData();
        BBBManager_Cache_GroupHierarchy::getInstance()->getData();
        BBBManager_Cache_GroupsAccessProfile::getInstance()->getData();
        BBBManager_Cache_GroupsAccessProfile::getInstance()->getData();
        
        
        set_time_limit(0);
        $params = $this->_helper->params();
        $pUuid = $params['r'];
        
        $fullUpdate = true;
        $rUsers = array();
        
        if(file_exists(BBBManager_Util_AccessProfileChanges::getInstance()->getFileName()) && (strlen(file_get_contents(BBBManager_Util_AccessProfileChanges::getInstance()->getFileName())) > 0)){
            $rUsers = json_decode(file_get_contents(BBBManager_Util_AccessProfileChanges::getInstance()->getFileName()));
            $fullUpdate = false;
        }
        
        $groupsHierarchy = BBBManager_Cache_GroupHierarchy::getInstance()->getData();
        
        if($fullUpdate){
            BBBManager_Cache_Auth::getInstance()->clean();
            $ldapMembers = array();
            try{
                IMDT_Util_Ldap::getInstance()->findMembersRecursively($ldapMembers);
                $usersCount = count($ldapMembers);
            } catch (Exception $ex) {
                $usersCount = 0;
            }
        }else{
            $usersCount = 0;
        }
        
        $userModel = new BBBManager_Model_User();
        $groupModel = new BBBManager_Model_Group();
        
        $usersWithGroupsSelect = $userModel->select()->setIntegrityCheck(false);
        $usersWithGroupsSelect->from('user',array('user_id', 'login', 'name', 'auth_mode_id'));
        $usersWithGroupsSelect->where('1 = ?', new Zend_Db_Expr($userModel->getSqlStatementForActiveUsers()));
        $usersWithGroupsSelect->join('user_group','user_group.user_id = user.user_id',array('groups' => new Zend_Db_Expr('GROUP_CONCAT(distinct user_group.group_id SEPARATOR ",")')));
        $usersWithGroupsSelect->group(array('user_id', 'login', 'name', 'auth_mode_id'));
        
        if($fullUpdate == false){
            $usersWithGroupsSelect->where('user.user_id in(?)', $rUsers);
        }

        $usersWithGroupsCollection = $userModel->fetchAll($usersWithGroupsSelect);
        $rUsersWithGroups = ($usersWithGroupsCollection instanceof Zend_Db_Table_Rowset ? $usersWithGroupsCollection->toArray() : array());
        
        $usersCount += count($rUsersWithGroups);
        
        file_put_contents(BBBManager_Util_AccessProfileChanges::getInstance()->getProgressFileName($pUuid), Zend_Json::encode(array('totalCount'=>$usersCount, 'doneCount'=>0)));
        session_write_close();
        
        $localGroupsWithLdapMembers = $groupModel->findLocalGroupsByLdapGroup();
        $rLocalGroupsWithLdapMembers = ($localGroupsWithLdapMembers != null ? $localGroupsWithLdapMembers->toArray() : array());
        
        $rLdapGroupXLocalGroup = array();
        
        if(count($rLocalGroupsWithLdapMembers) > 0){
            foreach($rLocalGroupsWithLdapMembers as $localGroupWithLdapMember){
                $ldapGroups = $localGroupWithLdapMember['ldap_group_names'];
                $rLdapGroupNames = explode(';', $ldapGroups);
                foreach($rLdapGroupNames as $ldapGroupName){
                    $rLdapGroupXLocalGroup[$ldapGroupName] = $localGroupWithLdapMember['group_id'];
                }
            }
        }
        
        $groupNameXGroupInfo = array();
        
        foreach($groupsHierarchy as $groupId => $groupInfo){
            $grpInfo = $groupInfo;
            $grpInfo['group_id'] = $groupId;
            
            $groupNameXGroupInfo[$groupInfo['name']] = $grpInfo;
        }
        
        $doneCount = 0;
        
        if(count($ldapMembers) > 0){
            foreach($ldapMembers as $ldapMember){
                $doneCount++;
                
                file_put_contents(BBBManager_Util_AccessProfileChanges::getInstance()->getProgressFileName($pUuid), Zend_Json::encode(array('totalCount'=>$usersCount, 'doneCount'=>$doneCount)));
                session_write_close();
                
                $ldapGroupsNames = $ldapMember['memberof'];
                $fullUserLdapGroups = array();
                
                foreach($ldapGroupsNames as $ldapGroup){
                    $groupCleanName = IMDT_Util_Ldap::ldapNameToCleanName($ldapGroup);
                    $fullUserLdapGroups[] = $groupCleanName;
                    
                    if(isset($groupNameXGroupInfo[$groupCleanName]['parents'])){
                        foreach($groupNameXGroupInfo[$groupCleanName]['parents'] as $parent){
                            if(isset($groupNameXGroupInfo[$parent['name']])){
                                $fullUserLdapGroups[] = $groupNameXGroupInfo[$parent['name']]['name'];    
                            }
                        }
                    }
                }
                
                $userLocalGroups = array();
                
                if(is_array($fullUserLdapGroups) && (count($fullUserLdapGroups) > 0)){
                    foreach($fullUserLdapGroups as $userLdapGroupName){
                        if(isset($rLdapGroupXLocalGroup[$userLdapGroupName])){
                            $userLocalGroups[] = $rLdapGroupXLocalGroup[$userLdapGroupName];
                        }
                    }
                }
                
                $userInfo = array();
                $userInfo['localGroupsFromLdapGroups'] = $userLocalGroups;
                $userInfo['groups'] = array();
                $userId = IMDT_Util_Ldap::ldapNameToCleanName($ldapMember['dn']);
                $userInfo['ldap_cn'] = $userId;
                
                IMDT_Util_AccessProfile::generate($userInfo);
            }
        }
        
        if(count($rUsersWithGroups) > 0){
            foreach($rUsersWithGroups as $user){
                $doneCount++;
                
                file_put_contents(BBBManager_Util_AccessProfileChanges::getInstance()->getProgressFileName($pUuid), Zend_Json::encode(array('totalCount'=>$usersCount, 'doneCount'=>$doneCount)));
                session_write_close();
                
                $userInfo = array(
                    'groups'    => $user['groups'],
                    'id'        => $user['user_id']
                );
                
                IMDT_Util_AccessProfile::generate($userInfo);
            }
        }
        
        BBBManager_Util_AccessProfileChanges::getInstance()->changesMade();
        
        $this->view->response = array(
            'success'       => '1'
        );
    }
}