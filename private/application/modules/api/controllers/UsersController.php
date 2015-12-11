<?php

class Api_UsersController extends Zend_Rest_Controller {

    protected $_id;
    public $filters = array();

    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();

        $this->_id = $this->_getParam('id', null);

        $this->model = new BBBManager_Model_User();

        $this->select = $this->model->select()
                ->setIntegrityCheck(false)
                ->from('user', array('user_id', 'name', 'email', 'login', 'auth_mode_id', 'access_profile_id', 'observations', 'create_date', 'last_update', 'ldap_cn',
                    'valid_from', 'valid_to',
                    'actived' => new Zend_Db_Expr('IF((valid_from <= current_date or valid_from is null) and (valid_to >= current_date or valid_to is null),true, false)')))
                ->joinLeft(array('ug_local' => 'user_group'), 'ug_local.user_id = user.user_id', null)
                ->joinLeft(array('g_local' => 'group'), 'g_local.group_id = ug_local.group_id AND g_local.auth_mode_id = '.BBBManager_Config_Defines::$LOCAL_AUTH_MODE, array(
                    'groups' => new Zend_Db_Expr('GROUP_CONCAT(distinct g_local.group_id SEPARATOR ",")')
                ))
                ->joinLeft(array('ug_ldap' => 'user_group'), 'ug_ldap.user_id = user.user_id', null)
                ->joinLeft(array('g_ldap' => 'group'), 'g_ldap.group_id = ug_ldap.group_id AND g_ldap.auth_mode_id = '.BBBManager_Config_Defines::$LDAP_AUTH_MODE, array(
                    'ldapGroups' => new Zend_Db_Expr('GROUP_CONCAT(distinct g_ldap.group_id SEPARATOR ",")')
                ))
                ->group(array('user.user_id', 'user.name', 'user.email', 'user.login', 'user.auth_mode_id', 'user.access_profile_id', 'user.create_date', 'user.last_update', 'user.ldap_cn'))
                ->order('user.name asc');

        $this->columnValidators = array();
        $this->columnValidators['auth_mode_id'] = array(new Zend_Validate_NotEmpty(), new Zend_Validate_Int());
        $this->columnValidators['name'] = array(new Zend_Validate_NotEmpty());
        $this->columnValidators['login'] = array(new Zend_Validate_NotEmpty());
        $this->columnValidators['email'] = array(new Zend_Validate_NotEmpty(), new Zend_Validate_EmailAddress());
        //$this->columnValidators['access_profile_id'] = array(new Zend_Validate_NotEmpty(), new Zend_Validate_Int());
        $this->columnValidators['valid_from'] = array(new Zend_Validate_Date(array('format' => 'yyyy-MM-dd')));
        $this->columnValidators['valid_to'] = array(new Zend_Validate_Date(array('format' => 'yyyy-MM-dd')));

        $this->optExclude = null;

        if ($this->_id != null)
            $this->optExclude = new Zend_Db_Expr("user_id != " . $this->_id);

        $this->columnValidators['email'][] = new Zend_Validate_Db_NoRecordExists(array('table' => 'user', 'field' => 'email', 'exclude' => $this->optExclude));
        $this->columnValidators['login'][] = new Zend_Validate_Db_NoRecordExists(array('table' => 'user', 'field' => 'login', 'exclude' => $this->optExclude));

        $this->filters['user_id'] = array('column' => 'user.user_id', 'type' => 'integer');
        $this->filters['name'] = array('column' => 'user.name', 'type' => 'text');
        $this->filters['main_name'] = array('column' => 'user.name', 'type' => 'text');
        $this->filters['login'] = array('column' => 'user.login', 'type' => 'text');
        $this->filters['email'] = array('column' => 'user.email', 'type' => 'text');
        $this->filters['auth_mode_id'] = array('column' => 'user.auth_mode_id', 'type' => 'integer');
        $this->filters['access_profile_id'] = array('column' => 'user.access_profile_id', 'type' => 'integer');
        $this->filters['observations'] = array('column' => 'user.observations', 'type' => 'text');
        $this->filters['valid_from'] = array('column' => 'user.valid_from', 'type' => 'date');
        $this->filters['valid_to'] = array('column' => 'user.valid_to', 'type' => 'date');
        $this->filters['actived'] = array('column' => 'IF((valid_from <= current_date or valid_from is null) and (valid_to >= current_date or valid_to is null),true, false)', 'type' => 'boolean');

        $this->filters['group'] = array('column' => 'user_group.group_id', 'type' => 'exists_key', 'select' => 'select 1 from user_group where user_group.user_id = user.user_id');
        $this->filters['group_name'] = array('column' => '`group`.name', 'type' => 'exists_text', 'select' => 'select 1 from user_group join `group` using(group_id) where user_group.user_id = user.user_id');
        $this->filters['group_auth_mode'] = array('column' => '`group`.auth_mode_id', 'type' => 'exists_key', 'select' => 'select 1 from user_group join `group` using(group_id) where user_group.user_id = user.user_id');


        $optUkExclude = null;
        if ($this->_id != null) {
            $optUkExclude = new Zend_Db_Expr("user_id != " . $this->_id);
        }

        //$this->columnValidators['login'][] = new Zend_Validate_Alnum();
        $this->columnValidators['login'][] = new Zend_Validate_Regex('/^[\w.-]*$/');
        $this->columnValidators['login'][] = new Zend_Validate_Db_NoRecordExists(array('table' => 'user', 'field' => 'login', 'exclude' => $this->optExclude));

        //$this->filters['source_ip'] = array('column'=>'network','type'=>'text');
        //TODO source-Ip
        $this->acessLog();
    }

    public function acessLog() {
        $old = null;
        $new = null;
        $desc = '';
        if ($this->_getParam('id', false)) {
            $this->getAction();
            if ($this->view->response['success'] == '1') {
                if (in_array($this->getRequest()->getActionName(), array('delete', 'put'))) {
                    $old = $this->view->response['row'];
                }
                $desc = $this->view->response['row']['name'] . ' (' . $this->view->response['row']['user_id'] . ')';
            }
            $this->view->response = null;
        }

        if (in_array($this->getRequest()->getActionName(), array('post', 'put'))) {
            $new = $this->_helper->params();
        }

        IMDT_Util_Log::write($desc, $new, $old);
    }

    public function rowHandler(&$row) {
        //$row['name'] = 'TESTEE';

        $row['access_profile'] = BBBManager_Config_Defines::getAccessProfile($row['access_profile_id']);
        $row['auth_mode'] = BBBManager_Config_Defines::getAuthMode($row['auth_mode_id']);
        $row['actived'] = $row['actived'] ? $this->_helper->translate('Yes') : $this->_helper->translate('No');

        $row['_editable'] = '0';
        $row['_removable'] = '0';

        $userAcessProfilesId = IMDT_Util_Auth::getInstance()->get('access_profile_id');
        $hasSupportProfile = ($userAcessProfilesId == BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE);
        $hasPrivilegedUserProfile = ($userAcessProfilesId == BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE);
        $hasAdministratorProfile = ($userAcessProfilesId == BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE);

        if ($hasPrivilegedUserProfile && $row['access_profile_id'] == BBBManager_Config_Defines::$SYSTEM_USER_PROFILE) {
            $row['_editable'] = '1';
            $row['_removable'] = '1';
        }

        if ($hasSupportProfile || $hasAdministratorProfile) {
            $row['_editable'] = '1';
            $row['_removable'] = '1';
        }

        if (IMDT_Util_Auth::getInstance()->get('globalWrite') == false) {
            $row['_editable'] = '0';
            $row['_removable'] = '0';
        }

        if($row['auth_mode_id'] == BBBManager_Config_Defines::$LDAP_AUTH_MODE) {
            $row['_removable'] = '0';
        }

        /* $row['_editable'] = '1';
          $row['_removable'] = '1'; */
    }

    public function deleteAction() {
        try {

            //Privileged user can only delete system users
            if (IMDT_Util_Auth::getInstance()->get('access_profile_id') == BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE) {
                $isPrivilegedUser = true;
            } else {
                $isPrivilegedUser = false;
            }


            if (strpos($this->_id, ',') == -1) {
                $rowModel = $this->model->find($this->_id)->current();
                if ($rowModel == null)
                    throw new Exception(sprintf($this->_helper->translate('User %s not found.'), $this->_id));

                if ($isPrivilegedUser && $rowModel->access_profile_id != BBBManager_Config_Defines::$SYSTEM_USER_PROFILE)
                    throw new Exception('No permission to perform this action.');

                $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('The user was deleted successfully.'));
                $rowModel->delete();
            } else {
                $collection = $this->model->find(explode(',', $this->_id));
                $i = 0;
                foreach ($collection as $row) {
                    if ($isPrivilegedUser && $row->access_profile_id != BBBManager_Config_Defines::$SYSTEM_USER_PROFILE)
                        throw new Exception('No permission to perform this action.');
                    $row->delete();
                    $i++;
                }

                $this->view->response = array('success' => '1', 'msg' => sprintf($this->_helper->translate('%s users was deleted successfully.'), $i));
            }
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function getAction() {
        try {

            $this->select->where('user.user_id = ?', $this->_id);

            $rowModel = $this->model->fetchRow($this->select);
            $rowModel = $rowModel->toArray();

            if (count($rowModel) == 0)
                throw new Exception(sprintf($this->_helper->translate('User %s not found.'), $this->_id));

            if (IMDT_Util_Auth::getInstance()->get('access_profile_id') != BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE && IMDT_Util_Auth::getInstance()->get('access_profile_id') != BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE && IMDT_Util_Auth::getInstance()->get('access_profile_id') != BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE && IMDT_Util_Auth::getInstance()->get('id') != $this->_id) {
                throw new Exception($this->_helper->translate('No permission to perform this action.'));
            }

            $this->rowHandler($rowModel);

            $arrResponse = array();
            $arrResponse['row'] = $rowModel;
            $arrResponse['success'] = '1';
            $arrResponse['msg'] = $this->_helper->translate('User retrieved successfully.');

            $this->view->response = $arrResponse;
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function headAction() {
        //$this->getResponse()->appendBody("From headAction()");
    }

    public function indexAction() {
        set_time_limit(0);
        try {
            IMDT_Util_ReportFilterHandler::parseThisFilters($this->select, $this->filters);
            IMDT_Util_ReportFilterHandler::parseThisQueries($this->select, $this->filters);

            if ($groupId = $this->_request->getParam('user_group')) {
                $this->select->where('user_group.group_id = ?', $groupId);
            }
            //echo $this->select;
            $collection = $this->model->fetchAll($this->select);
            $rCollection = ($collection != null ? $collection->toArray() : array());

            array_walk($rCollection, array($this, 'rowHandler'));

            //$rCollection = array_slice($rCollection, 0 , 5);

            $this->view->response = array('success' => '1',
                'collection' => $rCollection,//array('huull'),//array_slice($rCollection, 0, 100),
                'msg' => sprintf($this->_helper->translate('%s users retrieved successfully.'), count($rCollection)));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function parseValidators($data) {
        $arrErrorMessages = array();

        foreach ($this->columnValidators as $column => $validators) {
            foreach ($validators as $currValidator) {
                $value = (isset($data[$column]) && strlen($data[$column]) > 0) ? $data[$column] : '';
                if ($value == null && (!$currValidator instanceof Zend_Validate_NotEmpty))
                    continue;

                if (!$currValidator->isValid($value)) {
                    foreach ($currValidator->getMessages() as $errorMessage) {
                        $arrErrorMessages[] = $this->_helper->translate('column-user-' . $column) . ': ' . $errorMessage;
                    }
                    break;
                }
            }
        }

        return $arrErrorMessages;
    }

    public function doConversions(&$row) {
        $data = $this->_helper->params();

        if (isset($data['password']) && strlen($data['password']) > 0) {
            $row->password = IMDT_Util_Hash::generate($data['password']);
            $this->columnValidators['password'] = array(new Zend_Validate_StringLength(6, 16));
        }

        if (isset($row->date_start)) {
            $row->date_start = IMDT_Util_Date::filterDateToApi($row->date_start);
        }

        if (isset($row->date_end)) {
            $row->date_end = IMDT_Util_Date::filterDateToApi($row->date_end);
        }
    }

    public function postAction() {
        $this->model->getAdapter()->beginTransaction();

        try {
            $data = $this->_helper->params();
            if (empty($data))
                throw new Exception($this->_helper->translate('Invalid Request.'));

            //Privileged User validations
            if (IMDT_Util_Auth::getInstance()->get('access_profile_id') == BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE) {
                $data['auth_mode_id'] = BBBManager_Config_Defines::$LOCAL_AUTH_MODE;
                $data['access_profile_id'] = BBBManager_Config_Defines::$SYSTEM_USER_PROFILE;
                $data['groups'] = '';
            }

            //Use prefix for local users
            if ($data['auth_mode_id'] == BBBManager_Config_Defines::$LOCAL_AUTH_MODE) {
                $newUserPrefix = IMDT_Util_Config::getInstance()->get('new_user_prefix');
                if (!empty($newUserPrefix))
                    $data['login'] = $newUserPrefix . $data['login'];
            }

            $arrErrorMessages = $this->parseValidators($data);
            if (count($arrErrorMessages) > 0) {
                $this->view->response = array('success' => '0', 'msg' => $arrErrorMessages);
                return;
            }

            $rowModel = $this->model->createRow();
            $columns = $this->model->info('cols');
            $rowValidColumns = array_flip($columns);


            foreach ($data as $field => $value) {
                if (isset($rowValidColumns[$field])) {
                    if (strlen($value) == 0) {
                        $rowModel->$field = null;
                    } else {
                        $rowModel->$field = $value;
                    }
                }
            }

            $userInfo = array('groups' => (isset($data['groups']) ? $data['groups'] : array()));
            IMDT_Util_AccessProfile::generate($userInfo);
            $rowModel->access_profile_id = $userInfo['user_access_profile'];

            $this->doConversions($rowModel);
            $newRowId = $rowModel->save();

            $this->_id = $newRowId;

            $groups = $data['groups'];
            if (strlen(trim($groups)) > 0) {
                $this->model->getAdapter()->query('insert into user_group(user_id,group_id) 
                                                        select ' . $this->_id . ', g.group_id 
                                                        from `group` g where g.group_id in (' . $groups . ')');

                BBBManager_Util_AccessProfileChanges::getInstance()->mustChange();
            }

            $this->model->getAdapter()->commit();
            $this->view->response = array('success' => '1', 'msg' => '', 'id' => $newRowId, 'msg' => $this->_helper->translate('User has been created successfully.'));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function putAction() {
        $this->model->getAdapter()->beginTransaction();

        try {
            if ($this->_id == null)
                $this->forward('post');

            $data = $this->_helper->params();
            if (empty($data))
                throw new Exception($this->_helper->translate('Invalid Request.'));

            if (isset($data['password']) && strlen($data['password']) > 0) {
                $this->columnValidators['password'] = array(new Zend_Validate_StringLength(6, 16));
            }

            //Privileged User validations
            if (IMDT_Util_Auth::getInstance()->get('access_profile_id') == BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE) {
                if (array_key_exists('login', $data))
                    unset($data['login']);
                if (array_key_exists('auth_mode_id', $data))
                    unset($data['auth_mode_id']);
                if (array_key_exists('access_profile_id', $data))
                    unset($data['access_profile_id']);
                if (array_key_exists('groups', $data))
                    unset($data['groups']);
            }

            if (!array_key_exists('login', $data)) {
                unset($this->columnValidators['login']);
            }

            if (!array_key_exists('auth_mode_id', $data)) {
                unset($this->columnValidators['auth_mode_id']);
            }

            if (!array_key_exists('access_profile_id', $data)) {
                unset($this->columnValidators['access_profile_id']);
            }

            $rowModel = $this->model->find($this->_id)->current();
            if ($rowModel == null)
                throw new Exception($this->_helper->translate('User %s not found.'));

            //Privileged user can only change system users
            if (IMDT_Util_Auth::getInstance()->get('access_profile_id') == BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE && $rowModel->access_profile_id != BBBManager_Config_Defines::$SYSTEM_USER_PROFILE) {
                throw new Exception($this->_helper->translate('No permission to perform this action.'));
            }

            if (IMDT_Util_Auth::getInstance()->get('access_profile_id') != BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE && IMDT_Util_Auth::getInstance()->get('access_profile_id') != BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE && IMDT_Util_Auth::getInstance()->get('access_profile_id') != BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE && IMDT_Util_Auth::getInstance()->get('id') != $this->_id) {

                throw new Exception($this->_helper->translate('No permission to perform this action.'));
            }

            if ($rowModel->auth_mode_id == BBBManager_Config_Defines::$PERSONA_AUTH_MODE) {
                $this->columnValidators['login'] = array();
                $this->columnValidators['login'][] = new Zend_Validate_Db_NoRecordExists(array('table' => 'user', 'field' => 'login', 'exclude' => $this->optExclude));
            }

            $arrErrorMessages = $this->parseValidators($data);
            if (count($arrErrorMessages) > 0) {
                $this->view->response = array('success' => '0', 'msg' => $arrErrorMessages);
                return;
            }

            $columns = $this->model->info('cols');
            $rowValidColumns = array_flip($columns);

            foreach ($data as $field => $value) {
                if (isset($rowValidColumns[$field])) {
                    if (strlen($value) == 0) {
                        $rowModel->$field = null;
                    } else {
                        $rowModel->$field = $value;
                    }
                }
            }

            $userInfo = array('groups' => (isset($data['groups']) ? $data['groups'] : array()));
            IMDT_Util_AccessProfile::generate($userInfo);
            $rowModel->access_profile_id = $userInfo['user_access_profile'];

            $this->doConversions($rowModel);

            if ($rowModel->auth_mode_id != BBBManager_Config_Defines::$LOCAL_AUTH_MODE) {
                unset($rowModel->valid_to);
                unset($rowModel->valid_from);
            }

            $rowModel->save();

            $currentGroupsSelect = $this->model->select()->setIntegrityCheck(false)->from('user_group')->where('user_id = ?', $this->_id);
            $currentGroups = $this->model->fetchAll($currentGroupsSelect);
            $currentGroups = ($currentGroups instanceof Zend_Db_Table_Rowset ? $currentGroups->toArray() : array());
            $rCurrentGroups = array();

            if (count($currentGroups) > 0) {
                foreach ($currentGroups as $currentGroup) {
                    $rCurrentGroups[] = $currentGroup['group_id'];
                }
            }

            if (isset($data['groups'])) {
                $groups = $data['groups'];
                if (strlen(trim($groups)) == 0) {
                    $this->model->getAdapter()->query('delete from user_group where user_id = ' . $this->_id);
                } else {
                    $this->model->getAdapter()->query('delete from user_group where user_id = ' . $this->_id . ' and group_id not in (' . $groups . ')');
                    $this->model->getAdapter()->query('insert into user_group(user_id,group_id) 
                                                            select ' . $this->_id . ', g.group_id
                                                            from `group` g where g.group_id in (' . $groups . ')
                                                            and not exists(select 1 from user_group ug
                                                                                        where ug.user_id = ' . $this->_id . ' 
                                                                                        and ug.group_id = g.group_id)');
                }

                if (((strlen(trim($groups)) > 0) || ((strlen(trim($groups)) == 0) && count($rCurrentGroups) > 0))) {
                    $mustValidaProfileChanges = (count(array_diff(explode(',', $groups), $rCurrentGroups)) > 0);
                    $mustValidaProfileChanges = $mustValidaProfileChanges || (count(array_diff($rCurrentGroups, explode(',', $groups))) > 0);

                    if ($mustValidaProfileChanges > 0) {
                        BBBManager_Util_AccessProfileChanges::getInstance()->mustChange();
                    }
                }
            }

            $this->model->getAdapter()->commit();
            $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('User has been successfully changed.'));
        } catch (Exception $e) {
            $this->model->getAdapter()->rollBack();
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function importAction() {
        $hasTransaction = false;
        try {
            $inputData = $this->_helper->params();
            $csvFileContents = (isset($inputData['file-contents']) ? $inputData['file-contents'] : null);

            if ($csvFileContents == null) {
                throw new Exception($this->_helper->translate('Invalid CSV file content'));
            }

            $this->model->getAdapter()->beginTransaction();
            $hasTransaction = true;
            $records = $this->model->importCsv($csvFileContents);
            $this->model->getAdapter()->commit();

            $this->view->response = array('success' => '1', 'msg' => sprintf($this->_helper->translate('%s records imported'), $records) . '.');
        } catch (Exception $e) {
            if ($hasTransaction == true) {
                $this->model->getAdapter()->rollback();
            }
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

}
