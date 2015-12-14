<?php

class Api_GroupsController extends Zend_Rest_Controller {

    protected $_id;
    public $filters = array();

    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();

        $this->_id = $this->_getParam('id', null);

        $this->model = new BBBManager_Model_Group();

        $this->columnValidators = array();
        $this->columnValidators['name'] = array(new Zend_Validate_NotEmpty());

        $this->filters['main_name'] = array('column' => 'g.name', 'type' => 'text');
        $this->filters['name'] = array('column' => 'g.name', 'type' => 'text');
        $this->filters['auth_mode_id'] = array('column' => 'g.auth_mode_id', 'type' => 'integer');
        $this->filters['access_profile_id'] = array('column' => 'g.access_profile_id', 'type' => 'integer');

        //$this->filters['user_attendee'] = array('column'=>'user_group.user_id','type'=>'integer');
        //$this->filters['user_attendee_login'] = array('column'=>'user.login','type'=>'text');
        //$this->filters['user_attendee_name'] = array('column'=>'user.name','type'=>'text');
        //$this->filters['user_attendee_auth_mode'] = array('column'=>'user.auth_mode_id','type'=>'text');

        $this->filters['user_attendee'] = array('column' => 'user_group.user_id', 'type' => 'exists', 'select' => 'select 1 from user_group where user_group.group_id = g.group_id');
        $this->filters['user_attendee_auth_mode'] = array('column' => 'user.auth_mode_id', 'type' => 'exists_key', 'select' => 'select 1 from user_group join user using(user_id) where user_group.group_id = g.group_id');
        $this->filters['user_attendee_login'] = array('column' => 'user.login', 'type' => 'exists_text', 'select' => 'select 1 from user_group join user using(user_id) where user_group.group_id = g.group_id');
        $this->filters['user_attendee_name'] = array('column' => 'user.name', 'type' => 'exists_text', 'select' => 'select 1 from user_group join user using(user_id) where user_group.group_id = g.group_id');

        $this->filters['group_attendee'] = array('column' => 'group_group.group_id', 'type' => 'exists', 'select' => 'select 1 from group_group where group_group.parent_group_id = g.group_id');
        $this->filters['group_attendee_auth_mode'] = array('column' => '`group`.auth_mode_id', 'type' => 'exists_key', 'select' => 'select 1 from group_group join `group` using(group_id) where group_group.parent_group_id = g.group_id');
        $this->filters['group_attendee_name'] = array('column' => '`group`.name', 'type' => 'exists_text', 'select' => 'select 1 from group_group join `group` using(group_id) where group_group.parent_group_id = g.group_id');

        $this->filters['observations'] = array('column' => 'g.observations', 'type' => 'text');

        //$this->filters['access_profile_id'] = array('column'=>'group.access_profile_id','type'=>'integer');
        //$this->filters['source_ip'] = array('column'=>'network','type'=>'text');

        $this->columnValidators['name'] = array(new Zend_Validate_NotEmpty());
        $this->columnValidators['auth_mode_id'] = array(new Zend_Validate_NotEmpty());
        $this->columnValidators['access_profile_id'] = array(new Zend_Validate_NotEmpty());


        $this->optExclude = ($this->_id != null) ? new Zend_Db_Expr("group_id != " . $this->_id) : null;
        $this->columnValidators['name'][] = new Zend_Validate_Db_NoRecordExists(array('table' => 'group', 'field' => 'name', 'exclude' => $this->optExclude));

        $this->select = $this->model->select()
                ->setIntegrityCheck(false)
                ->from(array('g' => 'group'), array('group_id', 'name', 'auth_mode_id', 'access_profile_id', 'observations', 'visible'))
                ->joinLeft(array('ug' => 'user_group'), 'ug.group_id = g.group_id', array('user_attendee' => new Zend_Db_Expr('GROUP_CONCAT(distinct ug.user_id SEPARATOR ",")')))
                //->joinLeft('user','user.user_id = user_group.user_id',array())
                ->joinLeft('group_group', 'group_group.parent_group_id = g.group_id', array(
                    'group_attendee_local' => new Zend_Db_Expr('GROUP_CONCAT(distinct case when g.auth_mode_id = 1 then g.group_id else null end SEPARATOR ",")'),
                    'group_attendee_ldap' => new Zend_Db_Expr('GROUP_CONCAT(distinct case when g.auth_mode_id = 2 then g.group_id else null end SEPARATOR ",")')
                ))
                //->where('case when g.auth_mode_id = ' . BBBManager_Config_Defines::$LDAP_AUTH_MODE . ' then visible = true else 1 = 1 end')
                ->group(array('g.group_id', 'g.name', 'g.auth_mode_id', 'g.access_profile_id'))
                ->order('g.name asc');

        if($this->_getParam('auth_mode_id', false) != BBBManager_Config_Defines::$LDAP_AUTH_MODE) {
            $this->select->where('case when g.auth_mode_id = ' . BBBManager_Config_Defines::$LDAP_AUTH_MODE . ' then visible = true else 1 = 1 end');
        }

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
                $desc = $this->view->response['row']['name'] . ' (' . $this->view->response['row']['group_id'] . ')';
            }
            $this->view->response = null;
        }

        if (in_array($this->getRequest()->getActionName(), array('post', 'put'))) {
            $new = $this->_helper->params();
        }

        IMDT_Util_Log::write($desc, $new, $old);
    }

    public function rowHandler(&$row) {
        $row['auth_mode'] = BBBManager_Config_Defines::getAuthMode($row['auth_mode_id']);
        $row['access_profile'] = BBBManager_Config_Defines::getAccessProfile($row['access_profile_id']);
        $row['_editable'] = '1';
        $row['_removable'] = '1';

        if ($row['auth_mode_id'] == 2) {
            $row['_removable'] = '0';
            $row['_editable'] = '0';

            $rName = explode(',', $row['name']);
            $row['name'] = current($rName);
        } else {
            if (IMDT_Util_Auth::getInstance()->get('globalWrite') == false) {
                $row['_editable'] = '0';
                $row['_removable'] = '0';
            }
        }
    }

    public function deleteAction() {
        try {
            if (strpos($this->_id, ',') == -1) {
                $rowModel = $this->model->find($this->_id)->current();
                if ($rowModel == null)
                    throw new Exception(sprintf($this->_helper->translate('Group %s not found.'), $this->_id));

                $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('The group was deleted successfully.'));
                $rowModel->delete();
            } else {
                $collection = $this->model->find(explode(',', $this->_id));
                $i = 0;
                foreach ($collection as $row) {
                    $row->delete();
                    $i++;
                }

                $this->view->response = array('success' => '1', 'msg' => sprintf($this->_helper->translate('%s groups was deleted successfully.'), $i));
            }
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function getAction() {
        try {
            $this->select->where('g.group_id = ?', $this->_id);
            $rowModel = $this->model->fetchRow($this->select);

            if ($rowModel == null)
                throw new Exception(sprintf($this->_helper->translate('Meeting Room %s not found.'), $this->_id));

            $rowModel = $rowModel->toArray();
            $this->rowHandler($rowModel);

            $arrResponse = array();
            $arrResponse['row'] = $rowModel;
            $arrResponse['success'] = '1';
            $arrResponse['msg'] = $this->_helper->translate('Group retrieved successfully.');

            $this->view->response = $arrResponse;
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function headAction() {
        //$this->getResponse()->appendBody("From headAction()");
    }

    public function indexAction() {
        try {
            IMDT_Util_ReportFilterHandler::parseThisFilters($this->select, $this->filters);
            IMDT_Util_ReportFilterHandler::parseThisQueries($this->select, $this->filters);

            if ($userId = $this->_request->getParam('user_group')) {
                $this->select->where('user_group.user_id = ?', $userId);
            }

            if ($groupId = $this->_request->getParam('group_group')) {
                $this->select->where('group_group.parent_group_id = ?', $groupId);
            }

            $collection = $this->model->fetchAll($this->select);
            $rCollection = ($collection != null ? $collection->toArray() : array());

            array_walk($rCollection, array($this, 'rowHandler'));

            $this->view->response = array('success' => '1', 'collection' => $rCollection, 'msg' => sprintf($this->_helper->translate('%s groups retrieved successfully.'), count($rCollection)));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function parseValidators($data) {
        $arrErrorMessages = array();

        foreach ($this->columnValidators as $column => $validators) {
            foreach ($validators as $currValidator) {
                $value = (isset($data[$column]) && strlen($data[$column]) > 0) ? $data[$column] : '';
                if (!$currValidator->isValid($value)) {
                    foreach ($currValidator->getMessages() as $errorMessage) {
                        $arrErrorMessages[] = $this->_helper->translate('column-group-' . $column) . ': ' . $errorMessage;
                    }
                    break;
                }
            }
        }

        return $arrErrorMessages;
    }

    public function doConversions(&$row) {
        
    }

    public function postAction() {
        $this->model->getAdapter()->beginTransaction();

        try {
            $data = $this->_helper->params();
            if (empty($data))
                throw new Exception($this->_helper->translate('Invalid Request.'));

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

            $this->doConversions($rowModel);
            $newRowId = $rowModel->save();

            $this->_id = $newRowId;

            $users = $data['user_attendee'];
            if (strlen(trim($users)) > 0) {
                $this->model->getAdapter()->query('insert into user_group(group_id,user_id) 
                                                        select ' . $this->_id . ', u.user_id 
                                                        from user u where u.user_id in (' . $users . ')');
            }

            $groups_local = $data['group_attendee_local'];
            $groups_ldap = $data['group_attendee_ldap'];
            if (strlen(trim($groups_local)) > 0 && strlen(trim($groups_ldap)) > 0) {
                $groups = trim($groups_local) . ',' . trim($groups_ldap);
            } else {
                $groups = trim($groups_local) . trim($groups_ldap);
            }

            if (strlen(trim($groups)) > 0) {
                $sqlInsert = 'insert into group_group(parent_group_id,group_id)
                                select ' . $this->_id . ', g.group_id
                                from `group` g where g.group_id in (' . $groups . ') and g.group_id != ' . $this->_id;
                $this->model->getAdapter()->query($sqlInsert);
            }

            $this->model->getAdapter()->commit();

            //BBBManager_Cache_GroupSync::getInstance()->clean();
            //BBBManager_Cache_GroupSync::getInstance()->getData();
            //BBBManager_Cache_GroupHierarchy::getInstance()->clean();
            //BBBManager_Cache_GroupHierarchy::getInstance()->getData();
            //BBBManager_Cache_GroupsAccessProfile::getInstance()->clean();
            //BBBManager_Cache_GroupsAccessProfile::getInstance()->getData();

            BBBManager_Util_AccessProfileChanges::getInstance()->mustChange();

            $this->view->response = array('success' => '1', 'msg' => '', 'id' => $newRowId, 'msg' => $this->_helper->translate('Group has been created successfully.'));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function putAction() {
        $this->model->getAdapter()->beginTransaction();

        unset($this->columnValidators['auth_mode_id']);

        try {
            if ($this->_id == null)
                $this->forward('post');

            $data = $this->_helper->params();
            if (empty($data))
                throw new Exception($this->_helper->translate('Invalid Request.'));

            $rowModel = $this->model->find($this->_id)->current();
            if ($rowModel == null)
                throw new Exception($this->_helper->translate('Group %s not found.'));

            $columns = $this->model->info('cols');
            $rowValidColumns = array_flip($columns);
            //debug($data);
            if ($rowModel->auth_mode_id == '2') { //Ldap
                $rowModel->access_profile_id = $data['access_profile_id'];
                $rowModel->save();
                $this->model->getAdapter()->commit();
                $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('Group has been successfully changed.'));
                return;
            }

            $arrErrorMessages = $this->parseValidators($data);
            if (count($arrErrorMessages) > 0) {
                $this->view->response = array('success' => '0', 'msg' => $arrErrorMessages);
                return;
            }

            foreach ($data as $field => $value) {
                if (isset($rowValidColumns[$field])) {
                    if (strlen($value) == 0) {
                        $rowModel->$field = null;
                    } else {
                        $rowModel->$field = $value;
                    }
                }
            }

            $this->doConversions($rowModel);
            $rowModel->save();

            $users = implode(',', array_filter(explode(',', $data['user_attendee'])));
            //Zend_Debug::dump($users); die;
            if (strlen(trim($users)) == 0) {
                $this->model->getAdapter()->query('delete from user_group where group_id = ' . $this->_id);
            } else {
                $this->model->getAdapter()->query('delete from user_group where group_id = ' . $this->_id . ' and user_id not in (' . $users . ')');
                $this->model->getAdapter()->query('insert into user_group(group_id,user_id) 
                                                        select ' . $this->_id . ', u.user_id
                                                        from user u where u.user_id in (' . $users . ')
                                                        and not exists(select 1 from user_group ug
                                                                                    where ug.group_id = ' . $this->_id . ' 
                                                                                    and ug.user_id = u.user_id)');
            }

            $groups_local = $data['group_attendee_local'];
            $groups_ldap = $data['group_attendee_ldap'];
            if (strlen(trim($groups_local)) > 0 && strlen(trim($groups_ldap)) > 0) {
                $groups = trim($groups_local) . ',' . trim($groups_ldap);
            } else {
                $groups = trim($groups_local) . trim($groups_ldap);
            }

            if (strlen(trim($groups)) == 0) {
                $this->model->getAdapter()->query('delete from group_group where parent_group_id = ' . $this->_id);
            } else {
                $this->model->getAdapter()->query('delete from group_group where parent_group_id = ' . $this->_id . ' and group_id not in (' . $groups . ')');
                $sqlInsert = 'insert into group_group(parent_group_id,group_id)
                                select ' . $this->_id . ', g.group_id
                                from `group` g where g.group_id in (' . $groups . ')  and g.group_id != ' . $this->_id . ' 
                                                            and not exists
                                                            (select 1 from group_group  gg
                                                            where gg.parent_group_id = ' . $this->_id . ' 
                                                            and gg.group_id = g.group_id)';

                $this->model->getAdapter()->query($sqlInsert);
            }

            //BBBManager_Cache_GroupSync::getInstance()->clean();
            //BBBManager_Cache_GroupHierarchy::getInstance()->clean();
            //BBBManager_Cache_GroupsAccessProfile::getInstance()->clean();

            BBBManager_Util_AccessProfileChanges::getInstance()->mustChange();

            $this->model->getAdapter()->commit();
            $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('Group has been successfully changed.'));
        } catch (Exception $e) {
            $this->model->getAdapter()->rollBack();
            echo $e->getMessage();
            echo $e->getTraceAsString(); die;
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
