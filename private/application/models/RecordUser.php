<?php

class BBBManager_Model_RecordUser extends Zend_Db_Table_Abstract {

    protected $_name = 'record_user';
    protected $_primary = array('record_id', 'user_id');
    protected $_referenceMap = array(
	'Record' => array(
	    'columns' => 'record_id',
	    'refTableClass' => 'BBBManager_Model_Record',
	    'refColumns' => 'record_id',
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