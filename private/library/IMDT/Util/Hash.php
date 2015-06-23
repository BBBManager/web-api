<?php

class IMDT_Util_Hash {

    private static $HASH_STRATEGIE_MD5 = 1;

    static public function generate($string, $strategie = null) {
	
	if($strategie == null){
	    $strategie = IMDT_Util_Hash::$HASH_STRATEGIE_MD5;
	}
	
	switch($strategie){
	    case IMDT_Util_Hash::$HASH_STRATEGIE_MD5:
	    default:
		return sha1('bbbmanager' . $string . IMDT_Util_String::reverse($string));
	    break;
	}
    }
}