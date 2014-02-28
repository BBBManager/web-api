<?php

class Api_SecurityController extends Zend_Rest_Controller {

    protected $_id;

    public function init() {
	$this->_helper->viewRenderer->setNoRender(true);
	$this->_helper->layout()->disableLayout();

	$this->_id = $this->_getParam('id', null);
	$this->_model = new BBBManager_Model_User();
    }

    public function deleteAction() {
	
    }

    public function getAction() {
	
    }

    public function headAction() {
	
    }

    public function indexAction() {
	$allowedMenuItens = array();

	foreach (BBBManager_Config_Defines::getAclResources() as $resource) {

	    if (IMDT_Util_Acl::getInstance()->isAllowed($resource, 'list') == true) {
		$allowedMenuItens[] = $resource;
	    }
	}

	$uniqueAllowedMenuItens = array_unique($allowedMenuItens);

	$this->view->response = array('success' => '1', 'allowedMenuItens' => $uniqueAllowedMenuItens, 'acl' => base64_encode(serialize(IMDT_Util_Acl::getInstance()->getAclRules())));
    }

    public function postAction() {
	
    }

    public function putAction() {
	
    }

}