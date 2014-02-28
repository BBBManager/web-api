<?php

class Api_UsersResetPasswordController extends Zend_Rest_Controller {

    protected $_id;

    public function init() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout()->disableLayout();
    
    	$this->_id = $this->_getParam('id', null);
    	$this->_model = new BBBManager_Model_User();
    }

    public function acessLog($userId) {
        $old = null;
        $new = null;
        $desc = '';
        
        IMDT_Util_Log::write($desc,$new,$old,$userId);
    }

    public function deleteAction() {
	
    }

    public function getAction() {
	
    }

    public function headAction() {
	
    }

    public function indexAction() {
	$email = $this->_getParam('email', null);
	$userId = $this->_getParam('user_id', null);

	if ($userId != null) {
	    $user = $this->_model->find($userId);

	    if ($user == null) {
		$this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('No user found with provided id') . '.');
	    } else {
		$user = $user->current();
		if ($user->auth_mode_id == BBBManager_Config_Defines::$LDAP_AUTH_MODE) {
		    $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('This is an AD user.') );
		}elseif ($user->auth_mode_id == BBBManager_Config_Defines::$PERSONA_AUTH_MODE) {
		    $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('This is a Persona user.'));
		}else {
		    $emailSentsuccessfully = $this->_model->sendNewPassword($user);

		    if ($emailSentsuccessfully == true) {
			$this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('We have send you an email with your new password') . '.');
		    } else {
			$this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Error sending email with the new password') . '.');
		    }
		}
                
                $this->acessLog($userId);
	    }
	} elseif ($email != null) {
	    $user = $this->_model->findByEmail($email);

	    if ($user == null) {
		$this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('No user found with provided email') . '.');
	    } else {
                $userId = $user->user_id;
                
		if ($user->auth_mode_id == BBBManager_Config_Defines::$LDAP_AUTH_MODE) {
		    $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('You are an AD user.'));
		}elseif ($user->auth_mode_id == BBBManager_Config_Defines::$PERSONA_AUTH_MODE) {
		    $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('You are a Mozilla Persona user.'));
		}else {
		    $emailSentsuccessfully = $this->_model->sendNewPassword($user);

		    if ($emailSentsuccessfully == true) {
			$this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('We have send you an email with your new password') . '.');
		    } else {
			$this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Error sending email with your new password') . '.');
		    }
		}
                
                $this->acessLog($userId);
	    }
	} else {
	    $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Invalid email address') . '.');
	}
    }

    public function postAction() {
	
    }

    public function putAction() {
	
    }

}