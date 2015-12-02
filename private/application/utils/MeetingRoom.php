<?php

class BBBManager_Util_MeetingRoom {

    private static $_preHash = 'bbbM@n@g3r';
    private static $_posHash = 'h@s5f0rC@llbaCk';

    public static function generateHash() {
        return sha1(self::$_preHash . self::$_posHash . IMDT_Util_String::reverse(self::$_posHash));
    }

    public static function getAllUsersEmail($meetingRoomId) {
        if ($meetingRoomId == null) {
            return null;
        }

        $meetingRoomModel = new BBBManager_Model_MeetingRoom();
        $memberCollection = $meetingRoomModel->findRoomMembers($meetingRoomId);

        if ($memberCollection == null) {
            return null;
        }

        $rMemberCollection = ($memberCollection != null ? $memberCollection->toArray() : array());

        $rEmailCollection = array();

        if (count($rMemberCollection) > 0) {
            foreach ($rMemberCollection as $member) {
                if ($member['user_id'] != null) {
                    $rEmailCollection[$member['email']] = $member['email'];
                }

                if ($member['user_id_from_group'] != null) {
                    $rEmailCollection[$member['user_email_from_group']] = $member['user_email_from_group'];
                }

                if ($member['group_auth_mode_id'] == BBBManager_Config_Defines::$LDAP_AUTH_MODE) {
                    $groupHierarchy = BBBManager_Cache_GroupHierarchy::getInstance()->getData();
                    $ldapGroupHierarchy = ( isset($groupHierarchy[$member['group_id']]) ? $groupHierarchy[$member['group_id']]['parents'] : array());
                    $ldapConfig = IMDT_Util_Ldap::getInstance()->getSettings();

                    if (count($ldapGroupHierarchy) > 0) {
                        foreach ($ldapGroupHierarchy as $parent) {

                            if ($parent['auth_mode_id'] != BBBManager_Config_Defines::$LDAP_AUTH_MODE) {
                                continue;
                            }

                            $usersFromLdapGroup = IMDT_Util_Ldap::getInstance()->fetchUsersFromGroup($parent['name']);

                            foreach ($usersFromLdapGroup as $groupMember) {
                                $memberEmail = $groupMember . $ldapConfig['ldap']['account_domain_name_short'];
                                $rEmailCollection[$memberEmail] = $memberEmail;
                            }
                        }
                    } else {
                        $usersFromLdapGroup = IMDT_Util_Ldap::getInstance()->fetchUsersFromGroup($member['group_name']);

                        foreach ($usersFromLdapGroup as $groupMember) {
                            $memberEmail = $groupMember . $ldapConfig['ldap']['account_domain_name_short'];
                            $rEmailCollection[$memberEmail] = $memberEmail;
                        }
                    }
                }
            }
        }

        $distinctEmailCollection = array_keys($rEmailCollection);

        file_put_contents(APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'log.log', http_build_query($distinctEmailCollection));
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
