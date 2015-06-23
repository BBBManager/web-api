<?php

class BBBManager_Model_AuthMode extends Zend_Db_Table_Abstract {

    protected $_name = 'auth_mode';
    protected $_primary = 'auth_mode_id';
    protected $_dependentTables = array('BBBManager_Model_Group',
	'BBBManager_Model_User');

    public function findAll() {
	$select = $this->select();
	$select->order(array('name'));

	return $this->fetchAll($select);
    }

}