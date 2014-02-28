<?php

class BBBManager_Model_MeetingRoomProfile extends Zend_Db_Table_Abstract {

    protected $_name = 'meeting_room_profile';
    protected $_primary = 'meeting_room_profile_id';
    protected $_dependentTables = array('BBBManager_Model_MeetingRoomGroup',
	'BBBManager_Model_MeetingRoomUser');

}