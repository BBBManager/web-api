<?php

class IMDT_Util_AccessProfile {

    public static function generate(&$userData) {

        //$userAccessProfiles = array($userData['access_profile_id']);
        //$userAccessProfiles[] = $userData['access_profile_id'];

        $userAccessProfiles = array();

        if (isset($userData['groups'])) {
            $groupXAccessProfileMapping = BBBManager_Cache_GroupsAccessProfile::getInstance()->getData();
            $userGroups = $userData['groups'];

            if (!is_array($userGroups)) {
                if (stripos($userGroups, ',') === false) {
                    $userGroups = array($userGroups);
                } else {
                    $userGroups = explode(',', $userGroups);
                }
            }

            foreach ($userGroups as $group) {
                $groupId = $group;

                if (is_array($groupId)) {
                    $groupId = $groupId['group_id'];
                }

                if (isset($groupXAccessProfileMapping[$groupId])) {
                    foreach ($groupXAccessProfileMapping[$groupId] as $groupAccessProfileId) {
                        $userAccessProfiles[] = $groupAccessProfileId;
                    }
                }
            }
        }

        /* defined in IMDT_Service_Auth_Adapter_Ldap */
        if (isset($userData['localGroupsFromLdapGroups']) && (is_array($userData['localGroupsFromLdapGroups']))) {
            if (!isset($groupXAccessProfileMapping)) {
                $groupXAccessProfileMapping = BBBManager_Cache_GroupsAccessProfile::getInstance()->getData();
            }

            foreach ($userData['localGroupsFromLdapGroups'] as $group) {
                $groupId = (is_array($group) ? $group['group_id'] : $group);
                foreach ($groupXAccessProfileMapping[$groupId] as $groupAccessProfileId) {
                    $userAccessProfiles[] = $groupAccessProfileId;
                }
            }
        }

        /*
          $userAccessProfiles = array_unique($userAccessProfiles);
          $userData['final_access_profiles'] = $userAccessProfiles;
         */

        $userAccessProfile = BBBManager_Config_Defines::$SYSTEM_USER_PROFILE;

        if (count($userAccessProfiles) == 0) {
            $userAccessProfiles = array(isset($userData['access_profile_id']) ? $userData['access_profile_id'] : BBBManager_Config_Defines::$SYSTEM_USER_PROFILE);
        }

        foreach ($userAccessProfiles as $accessProfileId) {
            if ($accessProfileId < $userAccessProfile) {
                $userAccessProfile = $accessProfileId;
            }
        }

        $userData['user_access_profile'] = $userAccessProfile;

        if (isset($userData['id'])) {
            $userModel = new BBBManager_Model_User();
            $whereStatement = $userModel->getAdapter()->quoteInto('user_id = ?', $userData['id']);
            $userModel->update(array('access_profile_id' => $userAccessProfile), $whereStatement);
        } elseif (isset($userData['ldap_cn'])) {
            $userModel = new BBBManager_Model_User();
            $whereStatement = $userModel->getAdapter()->quoteInto('ldap_cn = ?', $userData['ldap_cn']);
            $userModel->update(array('access_profile_id' => $userAccessProfile), $whereStatement);
        }
    }

}
