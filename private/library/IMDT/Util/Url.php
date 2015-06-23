<?php
class IMDT_Util_Url{
    public static function baseUrl(){
        return ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != '')) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/';
    }
	
	public static function arrayToUrl($arr) {
		$str = '';
		if(count($arr) > 0) {
			foreach($arr as $key=>$val) {
				$str .= empty($str) ? '?' : '&';
				$str .= $key.'='.$val;
			}
		}
		
		$str = str_replace(' ','+',$str);
		
		return $str;
	}
	
	
	public static function getThisParams($arrFilters) {
		$arrParams = array();
		
		$controller = Zend_Controller_Front::getInstance();
		$request = $controller->getRequest();
		
		foreach($arrFilters as $column=>$curr) {
			$value = $request->getParam($column,'');
			$condition = $request->getParam($column.'_c','e');
			if(strlen(trim($value)) == 0) continue;
			
			if($curr['type'] == 'text') {
				$arrParams[$column] = $value;
				$arrParams[$column.'_c'] = $condition;
			} elseif($curr['type'] == 'date') {
				if(preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/',$value)) {
		            $arr = explode('/',$value);
					$strDate = $arr[2].'-'.$arr[1].'-'.$arr[0];
		        } elseif(preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $value)) {
		            $strDate = $value;
		        } else {
		        	continue;
		        }
				$arrParams[$column] = $strDate;
				$arrParams[$column.'_c'] = $condition;
				
				if($condition == 'b') {
					$strDate2 = '';
					$value2 = $request->getParam($column.'_2','');
					if(preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/',$value2)) {
			            $arr = explode('/',$value2);
						$strDate2 = $arr[2].'-'.$arr[1].'-'.$arr[0];
			        } elseif(preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $value2)) {
			            $strDate2 = $value2;
			        }
					
					if(strlen($strDate2) > 0) $arrParams[$column.'_2'] = $strDate2;
					
				}
			} elseif($curr['type'] == 'datetime') {
				$strDate = IMDT_Util_Date::filterDatetimeToApi($value);
				if(strlen($strDate) == 0) continue;
				
				$arrParams[$column] = $strDate;
				$arrParams[$column.'_c'] = $condition;
				
				if($condition == 'b') {
					$strDate2 = '';
					$value2 = $request->getParam($column.'_2','');
					
					$strDate2 = IMDT_Util_Date::filterDatetimeToApi($value2);
					if(strlen($strDate2) > 0) $arrParams[$column.'_2'] = $strDate2;
					
				}
			}
		}
		
		return $arrParams;
	}

}