<?php

class Api_RoomByUrlController extends Zend_Rest_Controller {
    protected $_model;
    protected $_id;

    public function init() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout()->disableLayout();
    
    	$this->_model = new BBBManager_Model_MeetingRoom();
    	$this->_id = $this->_getParam('id', null);
    }
    
    public function rowHandler(&$row) {
        $row['_editable'] = '1';
        $row['_removable'] = '1';
    }

    public function deleteAction() {
	
    }

    public function getAction() {
	try {
	    $roomUrl = base64_decode($this->_id);
	    $roomRecord = $this->_model->findRoomByUrl($roomUrl);
	    
	    $rRoom = ($roomRecord != null ? $roomRecord->toArray() : array());
        
        $this->rowHandler($rRoom);
        
	    $this->view->response = array('success' => '1', 'data' => $rRoom, 'msg' => '');
	} catch (Exception $e) {
	    $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
	}
    }

    public function headAction() {
	
    }

    public function postAction() {
	$this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

    public function putAction() {
	$this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

    public function indexAction() {
        $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

}