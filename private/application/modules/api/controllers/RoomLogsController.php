<?php

class Api_RoomLogsController extends Zend_Rest_Controller {

    protected $_id;
    public $filters = array();

    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();

        $this->_id = $this->_getParam('id', null);

        $this->model = new BBBManager_Model_MeetingRoomLog();
        //$this->dateColumns = array();
        //$this->datetimeColumns = array('create_date');
        $this->requiredColumns = array('meeting_room_id' => $this->_helper->translate('Meeting Room'), 'user_id' => $this->_helper->translate('User'), 'meeting_room_action' => $this->_helper->translate('Action'));
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
                $desc = $this->view->response['row']['id'];
            }
            $this->view->response = null;
        }

        if (in_array($this->getRequest()->getActionName(), array('post', 'put'))) {
            $new = $this->_helper->params();
        }

        IMDT_Util_Log::write($desc, $new, $old);
    }

    public function rowHandler(&$row) {
        $row['meeting_room_action_name'] = IMDT_Util_Translate::_($row['meeting_room_action_name']);
    }

    public function deleteAction() {
        try {
            if (strpos($this->_id, ',') == -1) {
                $row = $this->model->find($this->_id)->current();
                if ($row == null)
                    throw new Exception(sprintf($this->_helper->translate('Log %s not found.'), $this->_id));

                $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('The log was deleted successfully.'));
                $row->delete();
            } else {
                $arrPrimary = $this->model->info('primary');
                $primaryKey = $arrPrimary[1];
                $this->model->delete($primaryKey . ' in (' . $this->_id . ')');
                $this->view->response = array('success' => '1', 'msg' => sprintf($this->_helper->translate('%s logs was deleted successfully.'), count(explode(',', $this->_id))));
            }
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function getAction() {
        try {
            $row = $this->model->find($this->_id)->current();
            if ($row == null)
                throw new Exception(sprintf($this->_helper->translate('Log %s not found.'), $this->_id));

            $row = $row->toArray();
            $this->rowHandler($row);

            $this->view->response = array('success' => '1', 'row' => $row, 'msg' => $this->_helper->translate('Log retrieved successfully.'));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function indexAction() {
        try {
            $select = $this->model->select()
                    ->setIntegrityCheck(false)
                    ->from('meeting_room_log')
                    ->join('user', 'user.user_id = meeting_room_log.user_id', array('user_name' => 'name'))
                    ->join('meeting_room', 'meeting_room.meeting_room_id = meeting_room_log.meeting_room_id', array('meeting_room_name' => 'name'))
                    ->join('meeting_room_action', 'meeting_room_action.meeting_room_action_id = meeting_room_log.meeting_room_action_id', array('meeting_room_action_name' => 'name'))
                    ->order('meeting_room_log.create_date desc');

            $this->filters['meeting_room_id'] = array('column' => 'meeting_room_log.meeting_room_id', 'type' => 'integer');
            $this->filters['user'] = array('column' => 'meeting_room_log.user_id', 'type' => 'integer');
            $this->filters['user_name'] = array('column' => 'user.name', 'type' => 'text');
            $this->filters['user_login'] = array('column' => 'user.login', 'type' => 'text');
            $this->filters['meeting_room_action_id'] = array('column' => 'meeting_room_log.meeting_room_action_id', 'type' => 'integer');
            $this->filters['create_date'] = array('column' => 'meeting_room_log.create_date', 'type' => 'datetime');
            $this->filters['ip_address'] = array('column' => 'meeting_room_log.ip_address', 'type' => 'text');

            IMDT_Util_ReportFilterHandler::parseThisFilters($select, $this->filters);
            IMDT_Util_ReportFilterHandler::parseThisQueries($select, $this->filters);

            $collection = $this->model->fetchAll($select);
            $rCollection = ($collection != null ? $collection->toArray() : array());

            array_walk($rCollection, array($this, 'rowHandler'));

            $this->view->response = array('success' => '1', 'collection' => $rCollection, 'msg' => $this->_helper->translate('%s logs retrieved successfully.', count($rCollection)));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function postAction() {
        try {
            $params = $this->_helper->params();
            if (empty($params))
                throw new Exception($this->_helper->translate('Invalid Request.'));

            $nRow = $this->model->createRow();
            $columns = $this->model->info('cols');
            $rowValidColumns = array_flip($columns);

            foreach ($params as $field => $value) {
                if (isset($rowValidColumns[$field])) {
                    $nRow->$field = $value;
                }
            }

            $nRowId = $nRow->save();

            $this->view->response = array('success' => '1', 'id' => $nRowId, 'msg' => $this->_helper->translate('Log has been created successfully.'));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function putAction() {
        try {
            if ($this->_id == null)
                $this->forward('post');

            $params = $this->_helper->params();
            if (empty($params))
                throw new Exception($this->_helper->translate('Invalid Request.'));

            $row = $this->model->find($this->_id)->current();
            if ($row == null)
                throw new Exception(sprintf($this->_helper->translate('Log %s not found.'), $this->_id));

            $columns = $this->model->info('cols');
            $rowValidColumns = array_flip($columns);

            foreach ($params as $field => $value) {
                if (isset($rowValidColumns[$field])) {
                    $row->$field = $value;
                }
            }

            $row->save();

            $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('Log has been successfully changed.'));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function headAction() {
        $this->getResponse()->appendBody("From headAction()");
    }

}
