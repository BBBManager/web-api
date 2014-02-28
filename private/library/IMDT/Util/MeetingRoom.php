<?php
class MPRS_Util_MeetingRoom{

    private static $_preHash = 'bbbM@n@g3r';
    private static $_posHash = 'h@s5f0rC@llbaCk';
    
    public static function generateHash(){
	return sha1(self::$_preHash . self::$_posHash . IMDT_Util_String::reverse(self::$_posHash));
    }
    
    public static function getAllUsersEmail($meetingRoomId){
	return array(
	    'diogo@imdt.com.br',
	    'gustavot@imdt.com.br',
	    'tiago@imdt.com.br',
	    'scaryshow@gmail.com',
	    'diogo.jacobs@gmail.com'
	);
    }
	
    
}