<?php

class BBBManager_Model_InviteTemplate extends Zend_Db_Table_Abstract {

    protected $_name = 'invite_template';
    protected $_primary = 'invite_template_id';
    protected $_referenceMap = array(
	'User' => array(
	    'columns' => 'user_id',
	    'refTableClass' => 'BBBManager_Model_User',
	    'refColumns' => 'user_id',
	    'onDelete' => self::SET_NULL,
	    'onUpdate' => self::CASCADE_RECURSE
	)
    );

}