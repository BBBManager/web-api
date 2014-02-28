<?php

class BBBManager_Model_AccessLog extends Zend_Db_Table_Abstract {

    protected $_name = 'access_log';
    protected $_primary = 'access_log_id';

    public function findAll() {
	$select = $this->select();
	$select->order(array('create_date'));

	return $this->fetchAll($select);
    }

}