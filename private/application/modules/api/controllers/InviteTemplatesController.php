<?php

class Api_InviteTemplatesController extends Zend_Rest_Controller {

    protected $_id;
    public $filters = array();

    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();

        $this->_id = $this->_getParam('id', null);

        $this->model = new BBBManager_Model_InviteTemplate();

        $this->columnValidators = array();
        $this->columnValidators['name'] = array(new Zend_Validate_NotEmpty());
        $this->columnValidators['subject'] = array(new Zend_Validate_NotEmpty());
        $this->columnValidators['body'] = array(new Zend_Validate_NotEmpty());

        $this->filters['subject'] = array('column' => 'subject', 'type' => 'text');
        $this->filters['name'] = array('column' => 'name', 'type' => 'text');
        $this->filters['main_name'] = array('column' => 'name', 'type' => 'text');
        $this->filters['create_date'] = array('column' => 'create_date', 'type' => 'datetime');

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
                $desc = $this->view->response['row']['name'] . ' (' . $this->view->response['row']['invite_template_id'] . ')';
            }
            $this->view->response = null;
        }

        if (in_array($this->getRequest()->getActionName(), array('post', 'put'))) {
            $new = $this->_helper->params();
        }

        IMDT_Util_Log::write($desc, $new, $old);
    }

    public function rowHandler(&$row) {
        $row['_editable'] = '1';
        $row['_removable'] = '1';

        if (IMDT_Util_Auth::getInstance()->get('globalWrite') == false) {
            $row['_editable'] = '0';
            $row['_removable'] = '0';
        }
    }

    public function deleteAction() {
        try {
            if (strpos($this->_id, ',') == -1) {
                $rowModel = $this->model->find($this->_id)->current();
                if ($rowModel == null)
                    throw new Exception(sprintf($this->_helper->translate('Invite template %s not found.'), $this->_id));

                $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('The invite template was deleted successfully.'));
                $rowModel->delete();
            } else {
                $collection = $this->model->find(explode(',', $this->_id));
                $i = 0;
                foreach ($collection as $row) {
                    $row->delete();
                    $i++;
                }

                $this->view->response = array('success' => '1', 'msg' => sprintf($this->_helper->translate('%s invite templates was deleted successfully.'), $i));
            }
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function getAction() {
        try {
            $rowModel = $this->model->find($this->_id);
            if ($rowModel->count() == 0) {
                throw new Exception(sprintf($this->_helper->translate('Invite template %s not found.'), $this->_id));
            }

            $rowModel = $rowModel->current()->toArray();

            $this->rowHandler($rowModel);

            $arrResponse = array();
            $arrResponse['row'] = $rowModel;
            $arrResponse['success'] = '1';
            $arrResponse['msg'] = $this->_helper->translate('Invite template retrieved successfully.');

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
            $select = $this->model->select();
            $select->order('create_date desc');

            IMDT_Util_ReportFilterHandler::parseThisFilters($select, $this->filters);
            IMDT_Util_ReportFilterHandler::parseThisQueries($select, $this->filters);

            $collection = $this->model->fetchAll($select);
            $rCollection = ($collection != null ? $collection->toArray() : array());

            array_walk($rCollection, array($this, 'rowHandler'));

            $authDataNs = new Zend_Session_Namespace('authData');
            $authData = $authDataNs->authData;

            $this->view->response = array('success' => '1', 'collection' => $rCollection, 'teste' => $authData, 'msg' => sprintf($this->_helper->translate('%s invite templates retrieved successfully.'), count($rCollection)));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function parseValidators($data) {
        $arrErrorMessages = array();

        foreach ($this->columnValidators as $column => $validators) {
            foreach ($validators as $currValidator) {
                $value = $data[$column] ? : '';
                if (!$currValidator->isValid($value)) {
                    foreach ($currValidator->getMessages() as $errorMessage) {
                        $arrErrorMessages[] = $this->_helper->translate('column-invite_template-' . $column) . ': ' . $errorMessage;
                    }
                    break;
                }
            }
        }

        return $arrErrorMessages;
    }

    public function doConversions(&$row) {
        if (isset($row->date_start)) {
            $row->date_start = IMDT_Util_Date::filterDatetimeToApi($row->date_start);
        }

        if (isset($row->date_end)) {
            $row->date_end = IMDT_Util_Date::filterDatetimeToApi($row->date_end);
        }
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

            $this->model->getAdapter()->commit();
            $this->view->response = array('success' => '1', 'msg' => '', 'id' => $newRowId, 'msg' => $this->_helper->translate('Invite template has been created successfully.'));
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

            $arrErrorMessages = $this->parseValidators($data);
            if (count($arrErrorMessages) > 0) {
                $this->view->response = array('success' => '0', 'msg' => $arrErrorMessages);
                return;
            }

            $rowModel = $this->model->find($this->_id)->current();
            if ($rowModel == null)
                throw new Exception($this->_helper->translate('Invite template %s not found.'));

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
            $rowModel->save();

            $this->model->getAdapter()->commit();
            $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('Invite template has been successfully changed.'));
        } catch (Exception $e) {
            $this->model->getAdapter()->rollBack();
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

}
