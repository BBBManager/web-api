<?php

class BBBManager_Model_MeetingRoomUser extends Zend_Db_Table_Abstract {

    protected $_name = 'meeting_room_user';
    protected $_primary = array('meeting_room_id', 'user_id', 'meeting_room_profile_id');
    protected $_referenceMap = array(
	'User' => array(
	    'columns' => 'user_id',
	    'refTableClass' => 'BBBManager_Model_User',
	    'refColumns' => 'user_id',
	    'onDelete' => self::CASCADE_RECURSE,
	    'onUpdate' => self::CASCADE_RECURSE
	),
	'MeetingRoom' => array(
	    'columns' => 'meeting_room_id',
	    'refTableClass' => 'BBBManager_Model_MeetingRoom',
	    'refColumns' => 'meeting_room_id',
	    'onDelete' => self::CASCADE_RECURSE,
	    'onUpdate' => self::CASCADE_RECURSE
	),
	'MeetingRoomProfile' => array(
	    'columns' => 'meeting_room_profile_id',
	    'refTableClass' => 'BBBManager_Model_MeetingRoomProfile',
	    'refColumns' => 'meeting_room_profile_id',
	    'onDelete' => self::CASCADE_RECURSE,
	    'onUpdate' => self::CASCADE_RECURSE
	)
    );
    
    public function findByMeetingRoomId($meetingRoomId){
	$select = $this->select()->setIntegrityCheck(false)->distinct();
	$select->from(array('mru' => $this->_name), array('user_id'));
	$select->join('user', 'user.user_id = mru.user_id', array('email'));
	$select->where('mru.meeting_room_id = ?', $meetingRoomId);

	return $this->fetchAll($select);
    }

}