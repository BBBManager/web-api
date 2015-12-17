<?php

class BBBManager_Util_MeetingRoom {

    private static $_preHash = 'bbbM@n@g3r';
    private static $_posHash = 'h@s5f0rC@llbaCk';

    public static function generateHash() {
        return sha1(self::$_preHash . self::$_posHash . IMDT_Util_String::reverse(self::$_posHash));
    }

    public static function getAllUsersEmail($meetingRoomId) {
        if ($meetingRoomId == null || !is_numeric ($meetingRoomId) ) {
            return null;
        }
        
        $userModel = new BBBManager_Model_User();
        
        $sql = 'select distinct email from meeting_room mr
                inner join meeting_room_user mru on mr.meeting_room_id = mru.meeting_room_id
                inner join user u on mru.user_id = u.user_id
                where
                mr.meeting_room_id = ' . $meetingRoomId . ' and 
                ' . $userModel->getSqlStatementForActiveUsers();
        
        $sql .= ' union ';
        
        $sql .= 'SELECT distinct email FROM meeting_room mr 
                INNER JOIN meeting_room_group mrg ON mr.meeting_room_id = mrg.meeting_room_id
                inner join proc_user_groups pug on pug.group_id = mrg.group_id
                INNER JOIN user u ON pug.user_id = u.user_id
                where
                mr.meeting_room_id = ' . $meetingRoomId . ' and 
                ' . $userModel->getSqlStatementForActiveUsers();
        
        $distinctEmailCollection = $userModel->getDefaultAdapter()->fetchAll($sql);
        
        return $distinctEmailCollection;
    }

    public static function detectUserProfileInMeeting($rRoomsCollection) {
        $rRoomXInfo = array();
        $rRoomXProfile = array();

        foreach ($rRoomsCollection as $room) {
            if (!isset($rRoomXInfo[$room['meeting_room_id']])) {
                $rRoomXInfo[$room['meeting_room_id']] = array();
            }
            $rRoomXInfo[$room['meeting_room_id']][] = $room;
        }

        foreach ($rRoomXInfo as $roomInfo) {
            $userProfileInMeeting = BBBManager_Config_Defines::$ROOM_ATTENDEE_PROFILE;

            /* Removed this as asked in ticket 68

              if(IMDT_Util_Auth::getInstance()->get('user_access_profile') == BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE){
              $userProfileInMeeting = BBBManager_Config_Defines::$ROOM_ADMINISTRATOR_PROFILE;
              }
             */
            $roomDataInfo = current($roomInfo);
            $rRoomXProfile[$roomDataInfo['meeting_room_id']] = $roomDataInfo;

            if (isset($roomDataInfo['group_profile'])) {
                reset($roomInfo);
                foreach ($roomInfo as $roomDataInfo) {
                    if ($roomDataInfo['group_profile'] < $userProfileInMeeting) {
                        $userProfileInMeeting = $roomDataInfo['group_profile'];
                    }
                }
            }

            foreach ($roomInfo as $roomDataInfo) {
                if ($roomDataInfo['user_profile'] != NULL && $roomDataInfo['user_profile'] < $userProfileInMeeting) {
                    $userProfileInMeeting = $roomDataInfo['user_profile'];
                }
            }

            $rRoomXProfile[$roomDataInfo['meeting_room_id']]['user_profile_in_meeting'] = $userProfileInMeeting;
        }

        return $rRoomXProfile;
    }

}
