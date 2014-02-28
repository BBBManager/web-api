<?php

class BBBManager_Model_MeetingRoomAction extends Zend_Db_Table_Abstract {

    protected $_name = 'meeting_room_action';
    protected $_primary = 'meeting_room_action_id';
    protected $_dependentTables = array('BBBManager_Model_MeetingRoomLog');

}