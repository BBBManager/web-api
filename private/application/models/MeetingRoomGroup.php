<?php

class BBBManager_Model_MeetingRoomGroup extends Zend_Db_Table_Abstract {

    protected $_name = 'meeting_room_group';
    protected $_primary = array('meeting_room_id', 'group_id', 'auth_mode_id', 'meeting_room_profile_id');
    protected $_referenceMap = array(
	'Group' => array(
	    'columns' => 'group_id',
	    'refTableClass' => 'BBBManager_Model_Group',
	    'refColumns' => 'group_id',
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

    public function findMeetingGroupById($mId) {
	$select = $this->select();
	$select->from(array($this->_name), array('group_id', 'auth_mode_id', 'meeting_room_profile_id'));
	$select->where('meeting_room_id=?', $mId);

	return $this->fetchAll($select);
    }

}