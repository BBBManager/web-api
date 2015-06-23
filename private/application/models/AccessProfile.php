<?php

class BBBManager_Model_AccessProfile extends Zend_Db_Table_Abstract {

    protected $_name = 'access_profile';
    protected $_primary = 'access_profile_id';
    protected $_dependentTables = array('BBBManager_Model_User');

    public function findAll() {
	$select = $this->select();
	$select->order(array('name'));

	return $this->fetchAll($select);
    }

}