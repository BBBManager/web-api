<?php

class BBBManager_Model_RecordRecordTag extends Zend_Db_Table_Abstract {

    protected $_name = 'record_tag';
    protected $_primary = array('record_tag_id', 'record_id');
    protected $_referenceMap = array(
        'RecordTag' => array(
            'columns' => 'record_tag_id',
            'refTableClass' => 'BBBManager_Model_RecordTag',
            'refColumns' => 'record_tag_id',
            'onDelete' => self::CASCADE_RECURSE,
            'onUpdate' => self::CASCADE_RECURSE
        ),
        'Record' => array(
            'columns' => 'record_id',
            'refTableClass' => 'BBBManager_Model_Record',
            'refColumns' => 'record_id',
            'onDelete' => self::CASCADE_RECURSE,
            'onUpdate' => self::CASCADE_RECURSE
        )
    );

}
