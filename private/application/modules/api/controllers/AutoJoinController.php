<?php

class Api_AutoJoinController extends Zend_Rest_Controller {

    protected $_id;

    public function init() {
	$this->_helper->viewRenderer->setNoRender(true);
	$this->_helper->layout()->disableLayout();

	$this->_id = $this->_getParam('id', null);
    }

    public function deleteAction() {
	
    }

    public function getAction() {
	try{
		$autoJoin = BBBManager_Cache_AutoJoin::getInstance()->getData();
		$autoJoinData = (isset($autoJoin['auto-join-keys'][$this->_id]) ? $autoJoin['auto-join-keys'][$this->_id] : null);
        	if($autoJoinData == null){
			throw new Exception('Invalid API Session');
		}
			$authDataNs = new Zend_Session_Namespace('authData');
			$authDataNs->authData = $autoJoinData['authData'];

		$this->view->response = array('success' => '1', 'data' => $autoJoinData['clientData']);
	}catch(Exception $e){
		$this->view->response = array('success' => '0', 'msg' => $e->getMessage());
	}
    }

    public function headAction() {
	
    }

    public function indexAction() {
    }

    public function postAction() {
	
    }

    public function putAction() {
	
    }

}
