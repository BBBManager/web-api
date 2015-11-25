<?php
class Callback_MeetingRoomController extends IMDT_Controller_Abstract {

    private $_meetingRoomId;
    private $_receivedToken;
    private $_expectedToken;

    public function init() {
	$this->_meetingRoomId = $this->_getParam('meetingRoomId');
	$this->_receivedToken = $this->_getParam('tk');
	$this->_expectedToken = BBBManager_Util_MeetingRoom::generateHash();

	$this->_disableViewAndLayout();
    }

    public function indexAction() {
	try {
	    if ($this->_receivedToken != $this->_expectedToken) {
		throw new Exception('Invlaid token for meeting room');
	    } else {
		$logCount = $this->parseRequest($this->getAllParams());
	    }
	} catch (Exception $e) {
	    $this->_helper->json(array('success' => '0', 'msg' => $e->getMessage()));
	}
	
	$this->_helper->json(array('success' => '1', 'msg' => sprintf(IMDT_Util_Translate::_('%s log records successfully inserted.'), $logCount)));
    }

    public function parseRequest($inputData) {
	$logInfo = $inputData['xml'];
	$objLogInfo = simplexml_load_string($logInfo);
	
	$iLog = 0;
	
	foreach($objLogInfo as $logItem){
	    $ipAddress = $logItem->eventIPAddress;
	    $createDate = ((int) $logItem->eventTimestamp);
	    $rMeetingRoomAndUserId = explode('_', $logItem->userID);
	    $meetingRoomId = $rMeetingRoomAndUserId[0];
	    $userId = $rMeetingRoomAndUserId[1];
	    
	    $logData = array(
		'meeting_room_id'   => $meetingRoomId,
		'user_id'	    => $userId,
		'ip_address'	    => $ipAddress,
		'create_date'	    => date('Y-m-d H:i:s', $createDate)
	    );
	    
	    if($logItem->eventName == 'user_join'){
		$logData['meeting_room_action_id'] = 1;
	    }elseif($logItem->eventName == 'user_leave'){
		$logData['meeting_room_action_id'] = 2;
	    }
	    
	    $meetingRoomLogModel = new BBBManager_Model_MeetingRoomLog();
	    try{
		$meetingRoomLogModel->insert($logData);
	    }catch(Exception $e){
		throw new Exception($e->getMessage());
	    }
	    $iLog++;
	}
	return $iLog;
    }

}