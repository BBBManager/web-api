<?php
class IMDT_Util_Date {
	
	public static function filterStringToDate($date) {
		$currentFormat = IMDT_Util_Translate::_('dateFormat-php');
		
		if(strlen($date) == 0) {
			return false;
		} elseif($date[2] == '/' && preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/',$date)) {
			return Datetime::createFromFormat($currentFormat,$date);
        } elseif($date[2] == '-' && preg_match('/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/',$date)) {
			return Datetime::createFromFormat($currentFormat,str_replace('-','/',$date));
        } elseif($date[4] == '-' && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$date)) {
        	return Datetime::createFromFormat('Y-m-d',$date);
        } elseif($date[4] == '-' && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$date)) {
        	return Datetime::createFromFormat('Y-m-d',$date);
		} else {
			return false;
		}
	}
	
	public static function filterStringToDatetime($datetime) {
		$splited = split(' ', $datetime);
		if(count($splited) == 2) {
			list($date,$hms) = $splited;
		} else {
			$date = $splited[0];
			$hms = '';
		}
		
		$dateObject = self::filterStringToDate($date);
		if(!$dateObject) return false;
		
		if(strlen($hms) == 0) {
			$dateObject->setTime(0, 0, 0);
		} elseif(preg_match('/^[0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}$/',$hms)) {
			list($hour, $min, $sec) = split(':', $hms);
			$hour = (strlen($hour) == 2) ? $hour : '0'.$hour;
			$min = (strlen($min) == 2) ? $min : '0'.$min;
			$sec = (strlen($sec) == 2) ? $sec : '0'.$sec;
			
			$dateObject->setTime($hour, $min, $sec);
		} elseif(preg_match('/^[0-9]{1,2}:[0-9]{1,2}$/',$hms)) {
			list($hour, $min) = split(':', $hms);
			$hour = (strlen($hour) == 2) ? $hour : '0'.$hour;
			$min = (strlen($min) == 2) ? $min : '0'.$min;
			
			$dateObject->setTime($hour, $min, 0);
		}
		
		return $dateObject;
	}
	
	public static function filterDateToApi($date) {
		$outputFormat = 'Y-m-d';
		
		$dateObject = self::filterStringToDate($date);
		return $dateObject ? $dateObject->format($outputFormat) : '';
	}
	
	public static function filterDateToMysql($date) {
		if($dateObject = self::filterStringToDate($date)) {
			return new Zend_Db_Expr('STR_TO_DATE(\''.$dateObject->format('Y-m-d').'\', \'%Y-%m-%d\')');
		} else {
			return '';
		}
	}
	
	public static function filterDateToCurrentLang($date) {
		$outputFormat = IMDT_Util_Translate::_('dateFormat-php');
		
		$dateObject = self::filterStringToDate($date);
		return $dateObject ? $dateObject->format($outputFormat) : '';
	}
	
	public static function filterDatetimeToApi($datetime) {
		$outputFormat = 'Y-m-d H:i:s';
		
		$dateObject = self::filterStringToDatetime($datetime);
		return $dateObject ? $dateObject->format($outputFormat) : '';
	}
	
	public static function filterDatetimeToMysql($datetime) {
		if($dateObject = self::filterStringToDatetime($datetime)) {
			return new Zend_Db_Expr('STR_TO_DATE(\''.$dateObject->format('Y-m-d H:i:s').'\', \'%Y-%m-%d %T\')');
		} else {
			return '';
		}
	}
	
	public static function filterDatetimeToCurrentLang($datetime, $seconds = true) {
		if($seconds) {
			$outputFormat = IMDT_Util_Translate::_('dateFormat-php').' H:i:s';
		} else {
			$outputFormat = IMDT_Util_Translate::_('dateFormat-php').' H:i';
		}
		
		$dateObject = self::filterStringToDatetime($datetime);
		return $dateObject ? $dateObject->format($outputFormat) : '';
	}
    
    public static function diffExt($startSeconds,$endSeconds = null) {
        if($endSeconds == null) $endSeconds = time();
        if($endSeconds <= $startSeconds) return 'Cálculo de datas inválido';
        
        $diff_seconds = $endSeconds - $startSeconds;
        $diff_weeks = floor($diff_seconds/604800);
        $diff_seconds -= $diff_weeks * 604800;
        $diff_days = floor($diff_seconds/86400);
        $diff_seconds -= $diff_days * 86400;
        $diff_hours = floor($diff_seconds/3600);
        $diff_seconds -= $diff_hours * 3600;
        $diff_minutes = floor($diff_seconds/60);
        $diff_seconds -= $diff_minutes * 60;
        
        $arrFinal = array();
        if($diff_weeks > 0) $arrFinal[] = $diff_weeks.(($diff_weeks > 1) ? 'w' : 'w');
        if($diff_days > 0) $arrFinal[] = $diff_days.(($diff_days > 1) ? 'd' : 'd');
        if($diff_hours > 0) $arrFinal[] = $diff_hours.(($diff_hours > 1) ? 'hrs' : 'h');
        if($diff_minutes > 0) $arrFinal[] = $diff_minutes.(($diff_minutes > 1) ? 'mins' : 'm');
        if($diff_seconds > 0) $arrFinal[] = $diff_seconds.(($diff_seconds > 1) ? 'secs' : 's');
        
        $last = '';
        if(count($arrFinal) > 1) {
            $last = array_pop($arrFinal);
            return implode(', ', $arrFinal).' and '.$last;
        } else {
            return array_pop($arrFinal);
        }
    }
	
    public static function diff($startSeconds,$endSeconds = null) {
        if($endSeconds == null) $endSeconds = time();
        if($endSeconds <= $startSeconds) return 'Cálculo de datas inválido';
        
        $diff_seconds = $endSeconds - $startSeconds;
        $diff_weeks = floor($diff_seconds/604800);
        $diff_seconds -= $diff_weeks * 604800;
        $diff_days = floor($diff_seconds/86400);
        $diff_seconds -= $diff_days * 86400;
        $diff_hours = floor($diff_seconds/3600);
        $diff_seconds -= $diff_hours * 3600;
        $diff_minutes = floor($diff_seconds/60);
        $diff_seconds -= $diff_minutes * 60;
        
        $arrFinal = array();
        if($diff_weeks > 0) $arrFinal[] = $diff_weeks.(($diff_weeks > 1) ? 'w' : 'w');
        if($diff_days > 0) $arrFinal[] = $diff_days.(($diff_days > 1) ? 'd' : 'd');
        if($diff_hours > 0) $arrFinal[] = $diff_hours.(($diff_hours > 1) ? 'h' : 'h');
        if($diff_minutes > 0) $arrFinal[] = $diff_minutes.(($diff_minutes > 1) ? 'min' : 'min');
        if($diff_seconds > 0) $arrFinal[] = $diff_seconds.(($diff_seconds > 1) ? 's' : 's');
        
        $last = '';
        if(count($arrFinal) > 1) {
            return implode(':', $arrFinal);
        } else {
            return array_pop($arrFinal);
        }
    }
	
}