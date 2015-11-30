<?php

class Api_MaintenanceController extends Zend_Rest_Controller {

    protected $_id;
    public $filters = array();
    private $_maintenanceFile;

    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();

        if (IMDT_Util_Auth::getInstance()->get('id') != null) {
            $this->acessLog();
        }

        $this->columnValidators = array();
        $this->columnValidators['active'] = array(new Zend_Validate_NotEmpty());

        $this->_maintenanceFile = APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'maintenance';
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
            }
            $this->view->response = null;
        }

        if (in_array($this->getRequest()->getActionName(), array('post', 'put'))) {
            $new = $this->_helper->params();
        }

        IMDT_Util_Log::write($desc, $new, $old);
    }

    public function deleteAction() {
        
    }

    public function getAction() {
        try {
            if (file_exists($this->_maintenanceFile)) {
                $maintenanceObject = json_decode(file_get_contents($this->_maintenanceFile));

                $rowModel = array(
                    'active' => '1',
                    'description' => $maintenanceObject->description,
                    'hash' => $maintenanceObject->authorizationHash
                );
            } else {
                $rowModel = array(
                    'active' => '0'
                );
            }

            $arrResponse = array();
            $arrResponse['row'] = $rowModel;
            $arrResponse['success'] = '1';
            $arrResponse['msg'] = $this->_helper->translate('Maintenance retrieved successfully.');

            $this->view->response = $arrResponse;
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function headAction() {
        //$this->getResponse()->appendBody("From headAction()");
    }

    public function indexAction() {
        
    }

    public function parseValidators($data) {
        $arrErrorMessages = array();

        foreach ($this->columnValidators as $column => $validators) {
            foreach ($validators as $currValidator) {
                $value = (isset($data[$column]) && strlen($data[$column]) > 0) ? $data[$column] : '';
                if (!$currValidator->isValid($value)) {
                    foreach ($currValidator->getMessages() as $errorMessage) {
                        $arrErrorMessages[] = $this->_helper->translate('column-maintenance-' . $column) . ': ' . $errorMessage;
                    }
                }
            }
        }

        return $arrErrorMessages;
    }

    public function doConversions(&$row) {
        
    }

    public function postAction() {
        try {
            $data = $this->_helper->params();
            if (empty($data))
                throw new Exception($this->_helper->translate('Invalid Request.'));

            $arrErrorMessages = $this->parseValidators($data);

            if (count($arrErrorMessages) > 0) {
                $this->view->response = array('success' => '0', 'msg' => $arrErrorMessages);
                return;
            }

            $maintenanceDescription = $data['description'];
            $authorizationHash = BBBManager_Util_MaintenanceMode::generateAuthorizationHash($maintenanceDescription);

            $maintenanceObject = array(
                'description' => $maintenanceDescription,
                'authorizationHash' => $authorizationHash
            );

            BBBManager_Util_MaintenanceMode::notifyAdministrators($authorizationHash);

            file_put_contents($this->_maintenanceFile, json_encode($maintenanceObject));

            $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('Maintenance has been created successfully.'));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function putAction() {
        try {
            $data = $this->_helper->params();

            if (isset($data['active']) && (!in_array($data['active'], array(null, '0')))) {
                $this->forward('post');
            } else {
                unlink($this->_maintenanceFile);
            }
            $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('Maintenance has been successfully changed.'));
        } catch (Exception $e) {
            $this->model->getAdapter()->rollBack();
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

}
