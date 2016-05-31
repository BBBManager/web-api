<?php

class Api_MyRoomsController extends Zend_Rest_Controller {

    protected $_model;
    protected $_id;

    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();

        $this->_model = new BBBManager_Model_MeetingRoom();
        $this->_id = $this->_getParam('id', null);

        $this->filters['date_start'] = array('column' => 'date_start', 'type' => 'date');
        $this->filters['status'] = array('column' => 'status', 'type' => 'integer');
        $this->filters['meeting_room_id'] = array('column' => 'mr.meeting_room_id', 'type' => 'integer');
        $this->filters['recordings_count'] = array('column' => 'recordings_count', 'type' => 'integer');
        $this->acessLog();
    }

    public function acessLog() {
        $old = null;
        $new = null;
        $desc = '';
        if ($this->_getParam('id', false)) {
            $roomModel = $this->_model->find($this->_id)->current();
            //$desc = isset($this->view->response['row']) ? $this->view->response['row']['name'].' ('.$this->view->response['row']['meeting_room_id'].')' : '';
            $desc = $roomModel->name . ' (' . $roomModel->meeting_room_id . ')';
        }
        IMDT_Util_Log::write($desc, $new, $old);
    }

    public function deleteAction() {
        try {
            if ($this->_id == null) {
                throw new Exception('Invalid room id');
            }
            $userId = sprintf('%s_%s', $this->_id, IMDT_Util_Auth::getInstance()->get('id'));
            
            $rBbbApiRequest = array(
                'userId' => $userId,
                'adminKey' => trim(file_get_contents(IMDT_Util_Config::getInstance()->get('bbbmanager_agent_keyfile')))
            );

            $bbbApiRequestQueryString = http_build_query($rBbbApiRequest);

            $webBaseUrl = IMDT_Util_Config::getInstance()->get('bbbmanager_agent_baseurl');

            if (substr($webBaseUrl, -1) != '/') {
                $webBaseUrl .= '/';
            }
            
            $bbbApiResponseString = @file_get_contents($webBaseUrl . 'api/kill/?' . $bbbApiRequestQueryString);
            $bbbApiResponse = json_decode($bbbApiResponseString);
            
            if($bbbApiResponse == NULL) {
                $this->view->response = array(
                    'success' => '1',
                    'error' => 'Request to API failed'
                );
            } else if(isset($bbbApiResponse->success) && $bbbApiResponse->success != '1') {
                $this->view->response = array(
                    'success' => '1',
                    'error' => 'Failed to kill current session: ' . $bbbApiResponseString
                );
            } else if(isset($bbbApiResponse->success) && $bbbApiResponse->success == '1') {
                $this->view->response = array(
                    'success' => '1'
                );
            } else {
                die('Situação inesperada.');
            }
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function getAction() {
        try {
            if ($this->_id == null) {
                throw new Exception('Invalid room id');
            }

            $room = $this->_model->find($this->_id)->current();

            if ($room == null) {
                throw new Exception('Error fetching meeting room.');
            }

            $rRoom = $room->toArray();

            if ($rRoom['privacy_policy'] == BBBManager_Config_Defines::$PUBLIC_MEETING_ROOM) {
                $myRoomData = $this->_model->findMyRooms($this->_id, null, true);
            } else {
                $myRoomData = $this->_model->findMyRooms($this->_id);
            }

            $rMyRoomData = ($myRoomData != null ? $myRoomData->toArray() : array());

            if (count($rMyRoomData) == 0) {
                //throw new Exception('Error fetching meeting room information');
                $this->view->response = array(
                    'success' => '1',
                    'joinUrl' => '',
                    'accessable' => '0',
                    'roomName' => $rRoom['name']
                );

                return;
            }

            $rMyRoomData = BBBManager_Util_MeetingRoom::detectUserProfileInMeeting($rMyRoomData);
            $rMyRoomData = current($rMyRoomData);

            $userProfileInMeeting = $rMyRoomData['user_profile_in_meeting'];

            $rResponse = array(
                'success' => '',
                'joinUrl' => '',
                'msg' => '',
                'timeToWait' => '',
                'roomName' => $rMyRoomData['name'],
                'userProfileInMeeting' => $userProfileInMeeting
            );

            if ($rMyRoomData['status'] == BBBManager_Config_Defines::$ROOM_CLOSED) {
                $rResponse['success'] = '1';
                $rResponse['joinUrl'] = '';
                $rResponse['msg'] = $this->_helper->translate('The room is closed');
            } elseif ($rMyRoomData['status'] == BBBManager_Config_Defines::$ROOM_WAITING && $userProfileInMeeting == BBBManager_Config_Defines::$ROOM_ATTENDEE_PROFILE) {
                $roomStart = new DateTime(date('Y-m-d H:i:s', strtotime($rMyRoomData['date_start'])));
                $now = new DateTime();

                $tsDiference = $now->diff($roomStart);
                $strDiference = $tsDiference->format('%D:%H:%I:%S');

                $rResponse['success'] = '1';
                $rResponse['joinUrl'] = '';
                $rResponse['timeToWait'] = $strDiference;
                $rResponse['msg'] = '';
            } else {
                $roomStart = time();
                $roomEnd = strtotime($rMyRoomData['date_end']);
                $durationInSeconds = $roomEnd - $roomStart;

                $meetingId = $rMyRoomData['meeting_room_id'];
                $meetingName = $rMyRoomData['name'];
                $logoutUrl = IMDT_Util_Config::getInstance()->get('web_base_url');
                $maxParticipants = $rMyRoomData['participants_limit'];
                $record = $rMyRoomData['record'];
                $durationMinutes = ((int) ($durationInSeconds / 60));
                $userFullName = IMDT_Util_Auth::getInstance()->get('full_name');
                $userId = sprintf('%s_%s', $rMyRoomData['meeting_room_id'], IMDT_Util_Auth::getInstance()->get('id'));
                $userIpAddress = Zend_Controller_Front::getInstance()->getRequest()->getHeader('clientIpAddress');
                $callbackUrl = IMDT_Util_Config::getInstance()->get('api_base_url') . 'callback/meeting-room?tk=' . BBBManager_Util_MeetingRoom::generateHash();

                $welcomeMessage = sprintf($this->_helper->translate('Welcome to %s meeting.'), '<b>' . $meetingName . '</b>');
                //$welcomeMessage = '';

                $bbbRoleMapper = array(
                    BBBManager_Config_Defines::$ROOM_ADMINISTRATOR_PROFILE => 'M',
                    BBBManager_Config_Defines::$ROOM_MODERATOR_PROFILE => 'M',
                    BBBManager_Config_Defines::$ROOM_PRESENTER_PROFILE => 'M',
                    BBBManager_Config_Defines::$ROOM_ATTENDEE_PROFILE => 'A'
                );

                $userRoleInMeetingRoom = $bbbRoleMapper[$userProfileInMeeting];

                $presentersAndModeratorsUsersCollection = $this->_model->findModeratorsAndPresenters($this->_id);
                $rPresentersAndModeratorsUsers = ($presentersAndModeratorsUsersCollection != null ? $presentersAndModeratorsUsersCollection->toArray() : array());
                $rPresentersAndModeratorsUsersOrganized = array();

                if (count($rPresentersAndModeratorsUsers) > 0) {
                    foreach ($rPresentersAndModeratorsUsers as $roomUser) {
                        if (!isset($rPresentersAndModeratorsUsersOrganized[$roomUser['meeting_room_profile_id']])) {
                            $rPresentersAndModeratorsUsersOrganized[$roomUser['meeting_room_profile_id']] = array('users' => array());
                        }
                        $rPresentersAndModeratorsUsersOrganized[$roomUser['meeting_room_profile_id']]['users'][] = $roomUser['name'];
                    }

                    if (count($rPresentersAndModeratorsUsersOrganized) > 0) {
                        $welcomeMessage .= '<br/><br/>';

                        foreach ($rPresentersAndModeratorsUsersOrganized as $profile => $users) {
                            $welcomeMessage .= sprintf('<b>%s</b>', (count($users['users']) == 1 ? BBBManager_Config_Defines::getMemberRoomProfile($profile, false) : BBBManager_Config_Defines::getMemberRoomProfile($profile, true)));
                            $welcomeMessage .= '<ul>';

                            foreach ($users['users'] as $user) {
                                $welcomeMessage .= sprintf('<li>%s</li>', ucwords(strtolower($user)));
                            }
                            $welcomeMessage .= '</ul>';
                        }
                    }
                }

                $rBbbApiRequest = array(
                    'meetingId' => $meetingId,
                    'meetingName' => $meetingName,
                    'logoutUrl' => $logoutUrl,
                    'maxParticipants' => $maxParticipants,
                    'record' => $record,
                    'durationMinutes' => $durationMinutes,
                    'userFullName' => $userFullName,
                    'userId' => $userId,
                    'userIpAddress' => $userIpAddress,
                    'userRoleInMeeting' => $userRoleInMeetingRoom,
                    'adminKey' => trim(file_get_contents(IMDT_Util_Config::getInstance()->get('bbbmanager_agent_keyfile'))),
                    'welcomeMessage' => $welcomeMessage,
                    'callbackURL' => $callbackUrl,
                    'lockLockOnJoin' => $rMyRoomData['meeting_lock_on_start'],
                    'meetingMuteOnStart' => $rMyRoomData['meeting_mute_on_start'],
                    'lockDisableMicForLockedUsers' => $rMyRoomData['lock_disable_mic_for_locked_users'],
                    'lockDisableCamForLockedUsers' => $rMyRoomData['lock_disable_cam_for_locked_users'],
                    'lockDisablePublicChatForLockedUsers' => $rMyRoomData['lock_disable_public_chat_for_locked_users'],
                    'lockDisablePrivateChatForLockedUsers' => $rMyRoomData['lock_disable_private_chat_for_locked_users'],
                    'lockLockLayoutForLockedUsers' => $rMyRoomData['lock_layout_for_locked_users'],
                    'encrypt' => ($rMyRoomData['encrypted'] == true ? '1' : '0')
                );

                $bbbApiRequestQueryString = http_build_query($rBbbApiRequest);

                $webBaseUrl = IMDT_Util_Config::getInstance()->get('bbbmanager_agent_baseurl');

                if (substr($webBaseUrl, -1) != '/') {
                    $webBaseUrl .= '/';
                }

                $bbbApiResponse = file_get_contents($webBaseUrl . 'api/join/?' . $bbbApiRequestQueryString);
                $rResponse['success'] = '1';
                $rResponse['bbbApiResponse'] = $bbbApiResponse;
            }

            $this->view->response = $rResponse;
            $this->view->response['meetingRoomStatus'] = $rMyRoomData['status'];

            /* if the room is not yet running, but the user has administrator, moderator or presenter profile, the user needs to be able to enter in the room */
            if ($rMyRoomData['status'] == BBBManager_Config_Defines::$ROOM_WAITING && $userProfileInMeeting != BBBManager_Config_Defines::$ROOM_ATTENDEE_PROFILE) {
                $this->view->response['meetingRoomStatus'] = BBBManager_Config_Defines::$ROOM_OPENED;
            }
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function headAction() {
        
    }

    public function postAction() {
        
    }

    public function putAction() {
        
    }

    public function indexAction() {
        $select = $this->_model->select();
        IMDT_Util_ReportFilterHandler::parseThisFilters($select, $this->filters);
        IMDT_Util_ReportFilterHandler::parseThisQueries($select, $this->filters);

        try {
            $myRoomsCollection = $this->_model->findMyRooms(null, $select);
            $rCollection = ($myRoomsCollection != null ? $myRoomsCollection->toArray() : array());
            $rCollection = BBBManager_Util_MeetingRoom::detectUserProfileInMeeting($rCollection);
            $this->view->response = array('success' => '1', 'collection' => $rCollection, 'msg' => sprintf($this->_helper->translate('%s meeting rooms retrieved successfully.'), count($rCollection)));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

}
