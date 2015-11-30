<?php

class BBBManager_Model_RecordTag extends Zend_Db_Table_Abstract {

    protected $_name = 'record_tag';
    protected $_primary = 'record_tag_id';
    protected $_dependentTables = array('BBBManager_Model_RecordRecordTag');

}
