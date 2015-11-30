<?php

class BBBManager_Model_RecordGroup extends Zend_Db_Table_Abstract {

    protected $_name = 'record_group';
    protected $_primary = array('meeting_room_id', 'group_id', 'auth_mode_id', 'meeting_room_profile_id');
    protected $_referenceMap = array(
        'Record' => array(
            'columns' => 'record_id',
            'refTableClass' => 'BBBManager_Model_Record',
            'refColumns' => 'record_id',
            'onDelete' => self::CASCADE_RECURSE,
            'onUpdate' => self::CASCADE_RECURSE
        ),
        'Group' => array(
            'columns' => 'group_id',
            'refTableClass' => 'BBBManager_Model_Group',
            'refColumns' => 'group_id',
            'onDelete' => self::CASCADE_RECURSE,
            'onUpdate' => self::CASCADE_RECURSE
        )
    );

}
