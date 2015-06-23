<?php

class BBBManager_Model_IcProfile extends Zend_Db_Table_Abstract {

    protected $_name = 'ic_profile';
    protected $_primary = 'ic_profile_id';

    public function findAllIcProfile() {
	$select = $this->select();
	$select->from(array($this->_name));
	return $this->fetchAll($select);
    }

    public function findIcProfileById($icId = null) {
	$select = $this->select();
	$select->from(array($this->_name));
	$select->where('ic_profile_id=?', $icId);
	$select->limit(1);

	return $this->fetchAll($select);
    }
    
    public function importCsv($fileContents){
	//$csvRecords = $this->import_csv($fileContents);
        $csvRecords = IMDT_Util_Csv::import($fileContents);
	$cols = $this->info('cols');
	$validRecords = array();
	
	foreach($csvRecords as $record){
	    $validRecord = array();
	    foreach($record as $column => $value){
		if(array_search($column, $cols) !== false){
		    $validRecord[$column] = $value;
		}
	    }
	    $validRecords[] = $validRecord;
	}
	
	$recordCount = 0;
	
	foreach($validRecords as $record){
	    $pk = (is_array($this->_primary) ? current($this->_primary) : $this->_primary);
	    $update = false;
	    
	    if(isset($record[$pk]) && (! empty($record[$pk]))){
		$exists = $this->find($record[$pk])->current();
		
		if($exists != null){
		    $update = true;
		}
	    }
	    
	    if($update == true){
		$where = $this->getAdapter()->quoteInto($pk . ' = ?', $record[$pk]);
		$this->update($record, $where);
	    }else{
		$this->insert($record);
	    }
	    $recordCount++;
	}
	
	return $recordCount;
    }
}