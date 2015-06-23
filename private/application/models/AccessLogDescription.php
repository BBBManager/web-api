<?php

class BBBManager_Model_AccessLogDescription extends Zend_Db_Table_Abstract {

    protected $_name = 'access_log_description';
    protected $_primary = array('controller','action');
    
    
    public function findAll() {
	$select = $this->select();
	$select->order(array('description'));

	return $this->fetchAll($select);
    }

}