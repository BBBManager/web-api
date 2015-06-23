<?php

class Api_MoodleController extends Zend_Rest_Controller {

    public function init() {
        
    }

    public function acessLog() {
        
    }

    public function deleteAction() {
        
    }

    public function getAction() {
        
    }

    public function headAction() {
        
    }

    public function postAction() {
        
    }

    public function putAction() {
        
    }

    public function indexAction() {
        try {
            $meetingRoomId = $this->_getParam('meetingId', null);
            $username = $this->_getParam('user', null);
            $localuser = $this->_getParam('local', null);

            if (($meetingRoomId == null) || ($username == null) || ($localuser == null)) {
                throw new Exception('Invalid request');
            }

            $meetingRoomModel = new BBBManager_Model_MeetingRoom();
            $meetingRoom = $meetingRoomModel->findRoomById($meetingRoomId);

            if ($meetingRoom == null) {
                throw new Exception('Invalid meeting room');
            }

            $userModel = new BBBManager_Model_User();
            $user = $userModel->findByLogin($username);

            if ($user == null) {
                throw new Exception('Invalid user');
            }

            if ($user->status != '1') {
                throw new Exception('Blocked user');
            }

            $authResult = new IMDT_Service_Auth_Result(null);

            if ($localuser == '1') {
                $localAuthAdapter = new IMDT_Service_Auth_Adapter_Local();
                $authResult = $localAuthAdapter->authenticate($username, '', true);
            } else {
                $localAuthAdapter = new IMDT_Service_Auth_Adapter_Ldap();
                $authResult = $localAuthAdapter->authenticate($username, '', true);
            }

            if ($authResult->isValid()) {
                IMDT_Service_Auth::getInstance()->setAuthResult($authResult);
                IMDT_Service_Auth::getInstance()->afterSuccessfulAuthentication();

                $authDataNs = new Zend_Session_Namespace('authData');
                $authDataNs->authData = IMDT_Service_Auth::getInstance()->getAuthResult()->getAuthData();

                IMDT_Util_Acl::buildAcl();
            }

            $myRoom = $meetingRoomModel->findMyRooms($meetingRoomId);

            if (count($myRoom) == 0) {
                $meetingRoomUser = new BBBManager_Model_MeetingRoomUser();
                
                $meetingRoomUser->insert(array(
                    'user_id'   => IMDT_Util_Auth::getInstance()->get('id'),
                    'meeting_room_id'   => $meetingRoomId,
                    'meeting_room_profile_id' => BBBManager_Config_Defines::$ROOM_ATTENDEE_PROFILE,
                    'auth_mode_id' => ($localuser == '1' ? BBBManager_Config_Defines::$LOCAL_AUTH_MODE : BBBManager_Config_Defines::$LDAP_AUTH_MODE)
                ));
            }

            $clientData = IMDT_Service_Auth::getInstance()->getAuthResult()->getClientData();

            BBBManager_Cache_AutoJoin::getInstance()->add(array($clientData['token'] => array('authData' => $authDataNs->authData, 'clientData' => $clientData)));
            $this->view->response = array('success' => '1', 'token' => $clientData['token'], 'userId' => $clientData['id']);
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

}
