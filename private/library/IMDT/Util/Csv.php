<?php
class IMDT_Util_Csv{
    public static function import($fileContents, $delimiter = ';', $lineDelimiter = "\n"){
	$recordsRows = null;
	
	$rows = explode($lineDelimiter, $fileContents);
	$firstRow = current($rows);
	
	if(stripos($firstRow, 'sep=') !== false){
	    $delimiter = substr($firstRow, (strlen('sep=')), 1);
	    $recordRows = array_splice($rows,0,1);
	}
	
	if($recordsRows == null){
	    $recordRows = $rows;
	}
	
	$namesRow = array_shift($recordRows);
	$names = str_getcsv($namesRow, $delimiter);
        
        foreach($names as &$name){
            $name = strtolower($name);
        }
        
	$records = array();
	
	foreach($recordRows as $recordRow){
	    if(trim($recordRow) != ''){
		$rowValues = str_getcsv($recordRow,$delimiter);
		
		if($rowValues == null){
		    $rowValues = array_fill(0, count($names),null);
		}
		
		$records[] = array_combine($names, $rowValues);
	    }
	}
	
	return $records;
    }

}