<?php

class Api_RoomActionsController extends Zend_Rest_Controller {

    protected $_id;

    public function init() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout()->disableLayout();
    
    	$this->_id = $this->_getParam('id', null);
    	$this->model = new BBBManager_Model_MeetingRoomAction();
        $this->acessLog();
    }

    public function acessLog() {
        $old = null;
        $new = null;
        $desc = '';
        if($this->_getParam('id', false)) {
            $this->getAction();
            if($this->view->response['success'] == '1') {
                if(in_array($this->getRequest()->getActionName(),array('delete','put'))) {
                    $old = $this->view->response['row'];
                }
                $desc = $this->view->response['row']['name'].' ('.$this->view->response['row']['meeting_room_action_id'].')';
            }
            $this->view->response = null;
        }

        if(in_array($this->getRequest()->getActionName(),array('post','put'))) {
            $new = $this->_helper->params();
        }
        
        IMDT_Util_Log::write($desc,$new,$old);
    }

    public function rowHandler(&$row) {
        $row['name'] = IMDT_Util_Translate::_($row['name']);
        $row['_editable'] = '1';
        $row['_removable'] = '1';
    }

    public function getAction() {
	$this->model = new BBBManager_Model_MeetingRoom();

	try {
	    $row = $this->model->find($this->_id)->current();
	    if ($row == null)
		throw new Exception(sprintf($this->_helper->translate('Action %s not found.'), $this->_id));
        
        $row = $row->toArray();
        $this->rowHandler($row);
        
	    $this->view->response = array('success' => '1', 'row' => $row, 'msg' => $this->_helper->translate('Action retrieved successfully.'));
	} catch (Exception $e) {
	    $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
	}
    }
    
    public function indexAction() {
	try {
	    $select = $this->model->select();
	    $select->order('name');

	    $collection = $this->model->fetchAll($select);
        
	    $rCollection = ($collection != null ? $collection->toArray() : array());
        
        array_walk($rCollection, array($this, 'rowHandler'));

	    $this->view->response = array('success' => '1', 'collection' => $rCollection, 'msg' => $this->_helper->translate('%s actions retrieved successfully.', count($rCollection)));
	} catch (Exception $e) {
	    $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
	}
    }
    
    public function headAction() {
        $this->getResponse()->appendBody("From headAction()");
    }
    
    public function postAction() {
	   $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

    public function putAction() {
	   $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }
    
    public function deleteAction() {
       $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

}