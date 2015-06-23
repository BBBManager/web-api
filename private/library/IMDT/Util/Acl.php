<?php

class IMDT_Util_Acl {

    private static $_instance;
    private $_authData;
    private $_aclRules;

    public static function getInstance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        $authDataNs = new Zend_Session_Namespace('authData');
        $this->_authData = $authDataNs->authData;
        $this->_aclRules = $this->_authData['acl'];
    }

    public function isAllowed($resource, $privilege) {
        /* foreach ($this->_authData['final_access_profiles'] as $profileId) {
          $allowedByAcl = $allowedByAcl || $this->_aclRules->isAllowed($profileId, strtoupper($resource), strtoupper($privilege));

          if ($allowedByAcl == true) {
          break;
          }
          } */
        return $this->_aclRules->isAllowed($this->_authData['user_access_profile'], strtoupper($resource), strtoupper($privilege));
    }

    /* This method is called after successful login - Api_LoginController */

    public static function buildAcl() {
        $acl = new Zend_Acl();

        $admProfileRole = new Zend_Acl_Role(BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE);
        $supportProfileRole = new Zend_Acl_Role(BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE);
        $userProfileRole = new Zend_Acl_Role(BBBManager_Config_Defines::$SYSTEM_USER_PROFILE);
        $previlegedUserProfileRole = new Zend_Acl_Role(BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE);
        $naProfileRole = new Zend_Acl_Role(BBBManager_Config_Defines::$NA_PROFILE);

        $thisUser = new Zend_Acl_Role('loggedUser');

        foreach (BBBManager_Config_Defines::getAclResources() as $aclResource) {
            $acl->addResource(new Zend_Acl_Resource(strtoupper($aclResource)));
        }

        $acl->addRole($admProfileRole);
        $acl->addRole($supportProfileRole);
        $acl->addRole($userProfileRole);
        $acl->addRole($previlegedUserProfileRole);
        $acl->addRole($thisUser);
        $acl->addRole($naProfileRole);

        $resourceXRoleXPrivileges = array();
        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_ROOM_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'duplicate',
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                    'send_invites',
                    'import'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'INDEX',
                    'GET',
                    'PUT'
                ),
                'actions' => array(
                    'list',
                    'view'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'duplicate',
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                    'send_invites'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'PUT'
                ),
                'actions' => array(
                    'send_invites'
                )
            ),
            BBBManager_Config_Defines::$NA_PROFILE => array(
                'methods' => array(
                    'GET',
                ),
                'actions' => array(
                    'send_invites'
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_USER_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                    'send_new_password',
                    'import'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                    'PUT'
                ),
                'actions' => array(
                    'list',
                    'view',
                    'send_new_password'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'PUT',
                    'INDEX'
                ),
                'actions' => array(
                    'edit',
                    'view',
                    'send_new_password'
                )
            ),
            BBBManager_Config_Defines::$NA_PROFILE => array(
                'methods' => array(
                    'GET',
                    'PUT',
                    'INDEX'
                ),
                'actions' => array(
                    'edit',
                    'view',
                    'send_new_password'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                    'send_new_password'
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_GROUP_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                    'import'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'INDEX'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_USER_PROFILE => array(
                'methods' => array(
                    'INDEX'
                )
            ),
            BBBManager_Config_Defines::$NA_PROFILE => array(
                'methods' => array(
                    'INDEX'
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_SPEED_PROFILES_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                    'import'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_ACCESS_PROFILES_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'INDEX',
                    'GET'
                ),
                'actions' => array(
                    'list',
                    'view'
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_TAG_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_MY_ROOMS_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'INDEX',
                    'GET'
                ),
                'actions' => array(
                    'list',
                    'view'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'INDEX',
                    'GET'
                ),
                'actions' => array(
                    'list',
                    'view'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'INDEX',
                    'GET'
                ),
                'actions' => array(
                    'list',
                    'view'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_USER_PROFILE => array(
                'methods' => array(
                    'INDEX',
                    'GET'
                ),
                'actions' => array(
                    'list',
                    'view'
                )
            ),
            BBBManager_Config_Defines::$NA_PROFILE => array(
                'methods' => array(
                    'INDEX',
                    'GET'
                ),
                'actions' => array(
                    'list',
                    'view'
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_INVITE_TEMPLATE_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            )
        );
        
        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_MAINTENANCE_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_ROOM_LOGS_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_ROOM_AUDIENCE_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_ROOM_INVITES_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                    'POST'
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                    'POST'
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                    'POST'
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_TIMEZONE_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_BBB_CONFIGS_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_ROOM_ACTIONS_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_ACCESS_LOGS_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_ACCESS_LOG_DESCRIPTIONS_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_ACCESS_PROFILES_UPDATE_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'INDEX',
                    'PUT'
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_ROOM_USERS_IMPORT_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'INDEX',
                ),
                'actions' => array(
                    'import'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'INDEX',
                ),
                'actions' => array(
                    'import'
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'INDEX',
                ),
                'actions' => array(
                    'import'
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_CATEGORY_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'DELETE',
                    'GET',
                    'INDEX',
                    'POST',
                    'PUT'
                ),
                'actions' => array(
                    'edit',
                    'delete',
                    'insert',
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                )
            )
        );

        $resourceXRoleXPrivileges[BBBManager_Config_Defines::$ACL_RECORDINGS_RESOURCE] = array(
            BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                    'PUT'
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            ),
            BBBManager_Config_Defines::$SYSTEM_USER_PROFILE => array(
                'methods' => array(
                    'GET',
                    'INDEX',
                ),
                'actions' => array(
                    'list',
                    'view',
                )
            )
        );

        $authDataNs = new Zend_Session_Namespace('authData');
        //$userAccessProfiles = $authDataNs->authData['final_access_profiles'];
        $userAccessProfile = $authDataNs->authData['user_access_profile'];

        foreach ($resourceXRoleXPrivileges as $resource => $roleXPrivileges) {
            foreach ($roleXPrivileges as $role => $privileges) {
                if (isset($privileges['actions'])) {
                    $acl->allow($role, strtoupper($resource), array_map("strtoupper", $privileges['actions']));
                }

                if (isset($privileges['methods'])) {
                    $acl->allow($role, strtoupper($resource), array_map("strtoupper", $privileges['methods']));
                }

                //if (array_search($role, $userAccessProfiles) !== false) {
                if ($userAccessProfile == $role) {
                    if (isset($privileges['methods'])) {
                        $acl->allow($thisUser, strtoupper($resource), array_map("strtoupper", $privileges['methods']));
                    }

                    if (isset($privileges['actions'])) {
                        $acl->allow($thisUser, strtoupper($resource), array_map("strtoupper", $privileges['actions']));
                    }
                }
            }
        }

        $authDataNs->authData['acl'] = $acl;
    }

    public function getAclRules() {
        return $this->_aclRules;
    }

}
