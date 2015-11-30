<?php

class BBBManager_Model_Record extends Zend_Db_Table_Abstract {

    protected $_name = 'record';
    protected $_primary = 'record_id';
    protected $_dependentTables = array('BBBManager_Model_RecordUser',
        'BBBManager_Model_RecordGroup',
        'BBBManager_Model_RecordRecordTag');
    protected $_referenceMap = array(
        'MeetingRoom' => array(
            'columns' => 'meeting_room_id',
            'refTableClass' => 'BBBManager_Model_MeetingRoom',
            'refColumns' => 'meeting_room_id',
            'onDelete' => self::CASCADE_RECURSE,
            'onUpdate' => self::CASCADE_RECURSE
        )
    );

    public function findAllEvents() {
        $select = $this->select();
        $select->from(array($this->_name));

        return $this->fetchAll($select);
    }

    public function findEventById($evId) {
        $select = $this->select();
        $select->from(array($this->_name));
        $select->where('record_id=?', $evId);

        return $this->fetchAll($select);
    }

}
