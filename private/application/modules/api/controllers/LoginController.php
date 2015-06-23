<?php
class Api_LoginController extends Zend_Rest_Controller {
    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
    }

    
    public function getAction(){
    }
    
    public function deleteAction() {
    }

    public function headAction() {
    }

    public function indexAction() {
	$username = $this->_getParam('username', null);
	$password = $this->_getParam('password', null);
	
	$personaAssertion = $this->_getParam('personaAssertion', null);
	
	$usernameAndPasswordLogin = ($username != null && $password != null);
	$personaLogin = ($personaAssertion != null);
	
	try{
	    if($usernameAndPasswordLogin || $personaLogin){
		if($usernameAndPasswordLogin){
		    if(IMDT_Service_Auth::getInstance()->authenticate($username, $password) != false){
			$authDataNs = new Zend_Session_Namespace('authData');
			$authDataNs->authData = IMDT_Service_Auth::getInstance()->getAuthResult()->getAuthData();
                        /*echo '<pre>';
                        var_dump($authDataNs->authData);
                        echo '</pre>';die;*/

			IMDT_Util_Acl::buildAcl();
			
			$clientData = IMDT_Service_Auth::getInstance()->getAuthResult()->getClientData();

			$this->view->response = array('success' => '1', 'msg' => '', 'data' => $clientData);
		    }else{
			throw new Exception($this->_helper->translate('Invalid Credentials'));
		    }
		}

		if($personaLogin){
		    
		    $userName = $this->_getParam('userName', null);
		    
		    $personaAdapter = new IMDT_Service_Auth_Adapter_Persona(IMDT_Util_Config::getInstance()->get('persona_audience_url'));
		    $personaAuthentication = $personaAdapter->authenticate($personaAssertion, $userName);
		    
		    if($personaAuthentication == true){
			if(IMDT_Service_Auth::getInstance()->getAuthResult()->getNeedExtraInformation() == false){
			    $authDataNs = new Zend_Session_Namespace('authData');
			    $authDataNs->authData = IMDT_Service_Auth::getInstance()->getAuthResult()->getAuthData();

			    IMDT_Util_Acl::buildAcl();
			    
			    $clientData = IMDT_Service_Auth::getInstance()->getAuthResult()->getClientData();
			    $this->view->response = array('success' => '1', 'msg' => '', 'data' => $clientData);
			}else{
			    $this->view->response = array('success' => '1', 'msg' => '', 'extraInfo' => '1');
			}
		    }else{
			throw new Exception($this->_helper->translate('Persona authentication failed') . '!');
		    }
		    
		}
	    }else{
		throw new Exception($this->_helper->translate('Invalid Request.'));
	    }
	}catch(Exception $e){
	    throw new Exception($e->getMessage());
	}
    }

    public function postAction() {
    }

    public function putAction() {
    }
}
