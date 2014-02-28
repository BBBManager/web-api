<?php

class Api_ErrorController extends Zend_Rest_Controller {

    public function errorAction() {
	$errors = $this->_getParam('error_handler');
	
	$exception = $errors->exception;

	switch ($errors->type) {
	    case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
	    case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
	    case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:

		// 404 error -- controller or action not found
		$this->getResponse()->setHttpResponseCode(404);
		$this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('API Invalid Request URI'));
		break;
	    case (Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER && ($exception instanceof IMDT_Controller_Exception_InvalidToken)):
		$this->view->response = array('success' => '-1', 'msg' => $exception->getMessage());
	    break;
	    case (Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER && ($exception instanceof IMDT_Controller_Exception_AccessDennied)):
		$this->view->response = array('success' => '-2', 'msg' => $this->_helper->translate($exception->getMessage()));
	    break;
	    default:
		// application error
		$this->getResponse()->setHttpResponseCode(500);
		//$this->view->response = array('success'=>'-1','msg'=>$this->_helper->translate('Application error'));
		$exception = $errors['exception'];
		/* The translator object is not been used here, because the message is already translated in the throw call */
		$this->view->response = array('success' => '0', 'msg' => $exception->getMessage());
		break;
	}
    }

    public function indexAction() {
	die('oe');
	$error_msg = $this->_getParam('error_msg');

	if ($error_msg) {
	    $this->view->response = array('success' => '-1', 'msg' => $error_msg);
	} else {
	    $this->view->response = array('success' => '-1', 'msg' => $this->_helper->translate('Application error'));
	}
    }

    public function deleteAction() {
	
    }

    public function getAction() {
	
    }

    public function headAction() {
	
    }

    public function postAction() {
	
    }

    public function putAction() {
	
    }

}

