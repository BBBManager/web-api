<?php

class Api_RoomsAudienceController extends Zend_Rest_Controller
{

    protected $_id;
    public $filters = array();

    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();

        $this->_id = $this->_getParam('id', null);
        $this->model = new BBBManager_Model_MeetingRoomLog();
        $this->_roomsModel = new BBBManager_Model_MeetingRoom();
        $this->acessLog();
    }

    public function acessLog()
    {
        $old = null;
        $new = null;
        $desc = '';

        IMDT_Util_Log::write($desc, $new, $old);
    }

    public function deleteAction()
    {
        $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

    public function indexAction()
    {
        try {
            $params = $this->_request->getParams();
            $meetingRoomId = $params['meeting_room_id'];

            $userAcessProfileId = IMDT_Util_Auth::getInstance()->get('access_profile_id');
            $allowedRooms = null;

            //If it's not admin or system support, only allow access to rooms managed by this user
            //TODO move this logic to a reusable component
            if (!in_array($userAcessProfileId, array(BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE, BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE))) {
                $allowedRooms = array();
                //Get rooms that user have access and use as filter
                $myRoomsCollection = $this->_roomsModel->findMyRooms();
                $rCollection = ($myRoomsCollection != null ? $myRoomsCollection->toArray() : array());
                $rCollection = BBBManager_Util_MeetingRoom::detectUserProfileInMeeting($rCollection);

                foreach ($rCollection as $room) {
                    if (in_array($room['user_profile_in_meeting'], array(BBBManager_Config_Defines::$ROOM_MODERATOR_PROFILE, BBBManager_Config_Defines::$ROOM_ADMINISTRATOR_PROFILE)) != false) {
                        $allowedRooms[] = $room['meeting_room_id'];
                    }
                }
            }

            //Apply filter to rooms, only if it's not admin or support
            if ($allowedRooms != null && !in_array($meetingRoomId, $allowedRooms)) {
                throw new Exception(sprintf($this->_helper->translate('Meeting room %s not found.'), $meetingRoomId));
            }

            $modelMeetingRoom = new BBBManager_Model_MeetingRoom();
            $row = $modelMeetingRoom->find($meetingRoomId)->current();
            if ($row == null)
                throw new Exception(sprintf($this->_helper->translate('Meeting room %s not found.'), $meetingRoomId));

            $select = 'select auth_mode.name as auth_mode, hasjoined.user_id, u.login, u.name, hasjoined.ip_address, hasjoined.create_date as date_join,
                                    coalesce((select min(create_date) 
                                    from meeting_room_log hasleft 
                                    where hasleft.meeting_room_id = hasjoined.meeting_room_id
                                    and hasleft.user_id = hasjoined.user_id
                                    and hasleft.ip_address = hasjoined.ip_address
                                    and hasleft.meeting_room_action_id = 2
                                    and hasleft.create_date >= hasjoined.create_date
                                    ),current_timestamp) as date_left
                        from meeting_room_log hasjoined
                        join user u on u.user_id = hasjoined.user_id
                        join auth_mode on auth_mode.auth_mode_id = u.auth_mode_id
                        where hasjoined.meeting_room_id = ' . $meetingRoomId . '
                        and hasjoined.meeting_room_action_id = 1
                        order by u.name, hasjoined.create_date asc';

            $collectionLogs = $this->model->getDefaultAdapter()->fetchAll($select);

            $arrUsers = array();

            //date_default_timezone_set('America/Sao_paulo');

            foreach ($collectionLogs as $curr) {
                if (!isset($arrUsers[$curr['user_id']])) {
                    $arrUsers[$curr['user_id']] = array();
                    $arrUsers[$curr['user_id']]['auth_mode'] = $curr['auth_mode'];
                    $arrUsers[$curr['user_id']]['user_id'] = $curr['user_id'];
                    $arrUsers[$curr['user_id']]['login'] = $curr['login'];
                    $arrUsers[$curr['user_id']]['name'] = $curr['name'];
                    $arrUsers[$curr['user_id']]['ip_address'] = $curr['ip_address'];
                    $arrUsers[$curr['user_id']]['date_join'] = IMDT_Util_Date::filterDatetimeToCurrentLang($curr['date_join']);
                    $arrUsers[$curr['user_id']]['date_left'] = IMDT_Util_Date::filterDatetimeToCurrentLang($curr['date_left']);

                    $dateJoin = strtotime($curr['date_join']);
                    $dateLeft = strtotime($curr['date_left']);
                    $diff = $dateLeft - $dateJoin;
                    $arrUsers[$curr['user_id']]['online_time_seconds'] = $diff;
                    $arrUsers[$curr['user_id']]['online_time'] = IMDT_Util_Date::diff(0, $diff);
                } else {
                    $arrUsers[$curr['user_id']]['ip_address'] .= ', ' . $curr['ip_address'];
                    $arrUsers[$curr['user_id']]['date_join'] .= ', ' . IMDT_Util_Date::filterDatetimeToCurrentLang($curr['date_join']);
                    $arrUsers[$curr['user_id']]['date_left'] .= ', ' . IMDT_Util_Date::filterDatetimeToCurrentLang($curr['date_left']);

                    $dateJoin = strtotime($curr['date_join']);
                    $dateLeft = strtotime($curr['date_left']);
                    $diff = $dateLeft - $dateJoin;
                    $arrUsers[$curr['user_id']]['online_time_seconds'] += $diff;
                    $arrUsers[$curr['user_id']]['online_time'] = IMDT_Util_Date::diff(0, $arrUsers[$curr['user_id']]['online_time_seconds']);
                }
            }

            $this->view->response = array('success' => '1', 'collection' => $arrUsers, 'msg' => $this->_helper->translate('Room audience retrieved successfully.'));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function headAction()
    {
        $this->getResponse()->appendBody("From headAction()");
    }

    public function getAction()
    {
        $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

    public function postAction()
    {
        $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

    public function putAction()
    {
        $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

}
