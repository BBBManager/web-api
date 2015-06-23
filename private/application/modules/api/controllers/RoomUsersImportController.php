<?php

class Api_RoomUsersImportController extends Zend_Rest_Controller {
    public function init() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout()->disableLayout();
    }
    
    public function deleteAction() {
    }

    public function getAction() {
    }

    public function headAction() {
    }

    public function indexAction() {
    }

    public function postAction() {
    }

    public function putAction() {
    }
    
    public function importAction(){
	$hasTransaction = false;
        try{
	    $inputData = $this->_helper->params();
            
            $step = (isset($inputData['step']) ? $inputData['step'] : 'check');
            
            /*if($step == 'check'){*/
                $csvFileContents = (isset($inputData['file-contents']) ? $inputData['file-contents'] : null);
                if($csvFileContents == null){
                    throw new Exception($this->_helper->translate('Invalid CSV file content'));
                }
                $csvFileContents = base64_decode($csvFileContents);
                
                $users = IMDT_Util_Csv::import($csvFileContents);
                
                if(count($users) > 0){
                    $model = new BBBManager_Model_User();
                    $select = $model->select();
                    $select->where('auth_mode_id = ?', BBBManager_Config_Defines::$LDAP_AUTH_MODE);
                    $select->where('login in(?)', $users);
                    $dbUsers = $model->fetchAll($select);
                    
                    $dbUsers = (($dbUsers instanceof Zend_Db_Table_Rowset) ? $dbUsers->toArray() : array());
                    $rDbUsers = array();
                    
                    $validUsers = array();
                    $invalidUsers = array();
                    
                    if(count($dbUsers) > 0){
                        foreach($dbUsers as $dbUser){
                            $rDbUsers[strtolower($dbUser['login'])] = $dbUser;
                        }
                        
                        foreach($users as $user){
                            if(isset($rDbUsers[strtolower($user['login'])])){
                                $validUsers[$rDbUsers[strtolower($user['login'])]['user_id']] = $user['login'];
                            }else{
                                $invalidUsers[] = $user['login'];
                            }
                        }
                    }
                    $this->view->response = array('success' => '1', 'msg' => IMDT_Util_Translate::_('File processed successfully.'), 'data' => array('valid' => $validUsers, 'invalid' => $invalidUsers));
                }
            /*}else{
                $users = (isset($inputData['users']) ? $inputData['users'] : array());
                $permission = (isset($inputData['permission']) ? $inputData['permission'] : BBBManager_Config_Defines::$ROOM_ATTENDEE_PROFILE);
                $roomId = (isset($inputData['roomId']) ? $inputData['roomId'] : null);
                $userCount = 0;
                
                if($roomId != null && (count($users) > 0)){
                    $roomUserModel = new BBBManager_Model_MeetingRoomUser();
                    $roomUserModel->getAdapter()->beginTransaction();
                    $hasTransaction = true;
                    
                    foreach($users as $user){
                        
                        $meetingRoomUserXProfileSelect = $roomUserModel->select();
                        $meetingRoomUserXProfileSelect->where('meeting_room_id = ?', $roomId);
                        $meetingRoomUserXProfileSelect->where('user_id = ?', $user);
                        $meetingRoomUserXProfileSelect->where('meeting_room_profile_id = ?', $permission);
                        $meetingRoomUserXProfileSelect->where('auth_mode_id = ?', BBBManager_Config_Defines::$LDAP_AUTH_MODE);
                        
                        $meetingRoomUserXProfile = $roomUserModel->fetchRow($meetingRoomUserXProfileSelect);
                        
                        if($meetingRoomUserXProfile == null){
                            $rInsert = array(
                                'meeting_room_id'           => $roomId,
                                'user_id'                   => $user,
                                'meeting_room_profile_id'   => $permission,
                                'auth_mode_id'              => BBBManager_Config_Defines::$LDAP_AUTH_MODE
                            );

                            $roomUserModel->insert($rInsert);
                            $userCount++;
                        }
                    }
                    $roomUserModel->getAdapter()->commit();
                }
                $this->view->response = array('success' => '1', 'msg' => sprintf($this->_helper->translate('%s records imported'), $userCount) . '.');
            }*/
	}catch(Exception $e){
            if($hasTransaction == true){
                $roomUserModel->getAdapter()->rollback();
            }
	    $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
	}
    }
}