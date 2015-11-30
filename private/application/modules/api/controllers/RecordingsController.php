<?php

class Api_RecordingsController extends Zend_Rest_Controller {

    protected $_id;
    public $filters = array();

    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();

        $this->_id = $this->_getParam('id', null);

        $this->model = new BBBManager_Model_Record();

        $this->select = $this->model->select()
                ->setIntegrityCheck(false)
                ->from(
                array(
            'r' => 'record'
                ), array(
            'record_id',
            'name',
            'date_start',
            'date_end',
            'meeting_room_id',
            'bbb_id',
            'playback_url',
            'sync_done'
                )
        );

        $this->filters['meeting_room_id'] = array('column' => 'meeting_room_id', 'type' => 'integer');
        $this->filters['sync_done'] = array('column' => 'sync_done', 'type' => 'integer');
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
                $desc = $this->view->response['row']['name'] . ' (' . $this->view->response['row']['meeting_room_id'] . ')';
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
            $this->select->where('r.record_id = ?', $this->_id);
            $rowModel = $this->model->fetchRow($this->select);

            if ($rowModel == null)
                throw new Exception(sprintf($this->_helper->translate('Record %s not found.'), $this->_id));

            $rowModel = $rowModel->toArray();

            $arrResponse = array();
            $arrResponse['row'] = $rowModel;
            $arrResponse['success'] = '1';
            $arrResponse['msg'] = $this->_helper->translate('Record retrieved successfully.');

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
            $this->select->order('record_id');

            IMDT_Util_ReportFilterHandler::parseThisFilters($this->select, $this->filters);
            IMDT_Util_ReportFilterHandler::parseThisQueries($this->select, $this->filters);

            $collection = $this->model->fetchAll($this->select);
            $rCollection = ($collection != null ? $collection->toArray() : array());

            $this->view->response = array('success' => '1', 'collection' => $rCollection, 'msg' => sprintf($this->_helper->translate('%s recordings retrieved successfully.'), count($rCollection)));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function postAction() {
        
    }

    public function putAction() {
        $this->model->getAdapter()->beginTransaction();

        try {
            $data = $this->_helper->params();
            if (empty($data))
                throw new Exception($this->_helper->translate('Invalid Request.'));


            $rowModel = $this->model->find($this->_id)->current();
            if ($rowModel == null)
                throw new Exception($this->_helper->translate(sprintf('Recording %s not found.', $this->_id)));

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

            $rowModel->save();

            $this->model->getAdapter()->commit();
            $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('Recording has been successfully changed.'));
        } catch (Exception $e) {
            $this->model->getAdapter()->rollBack();
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

}
