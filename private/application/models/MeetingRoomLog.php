<?php

class BBBManager_Model_MeetingRoomLog extends Zend_Db_Table_Abstract {

    protected $_name = 'meeting_room_log';
    protected $_primary = 'id';
    protected $_referenceMap = array(
	'MeetingRoom' => array(
	    'columns' => 'meeting_room_id',
	    'refTableClass' => 'BBBManager_Model_MeetingRoom',
	    'refColumns' => 'meeting_room_id',
	    'onDelete' => self::CASCADE_RECURSE,
	    'onUpdate' => self::CASCADE_RECURSE
	),
	'MeetingRoomAction' => array(
	    'columns' => 'meeting_room_action_id',
	    'refTableClass' => 'BBBManager_Model_MeetingRoomAction',
	    'refColumns' => 'meeting_room_action_id',
	    'onDelete' => self::CASCADE_RECURSE,
	    'onUpdate' => self::CASCADE_RECURSE
	),
	'User' => array(
	    'columns' => 'user_id',
	    'refTableClass' => 'BBBManager_Model_User',
	    'refColumns' => 'user_id',
	    'onDelete' => self::CASCADE_RECURSE,
	    'onUpdate' => self::CASCADE_RECURSE
	)
    );

}