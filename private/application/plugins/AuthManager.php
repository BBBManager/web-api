<?php

class BBBManager_Plugin_AuthManager extends Zend_Controller_Plugin_Abstract {

    private $_authProvider;

    public function __construct() {
        $this->_authProvider = Zend_Auth::getInstance();
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        /* echo '<pre>';
          var_dump($request->getParams());
          die; */
        /* $requestingAuth = ($request->getModuleName() == 'login' && $request->getControllerName() == 'auth' && $request->getActionName() == 'auth');
          parse_str(file_get_contents('php://input'), $arguments);

          //$hasUserAndPassword = ((isset($arguments['user']) && $arguments['user'] != '') && ((isset($arguments['password']) && $arguments['password'] != '')));
          $hasUserIdAndToken = ((isset($arguments['userId']) && $arguments['userId'] != '') && ((isset($arguments['token']) && $arguments['token'] != ''))); */


        //echo IMDT_Util_Hash::generate('515457');       die('oi');

        $requestingPublicResource = ($request->getModuleName() == 'api' && $request->getControllerName() == 'login');
        $requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'room-by-url' && $request->getActionName() == 'get');
        $requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'public-rooms' && $request->getActionName() == 'index');
        $requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'public-rooms' && $request->getActionName() == 'get');
        $requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'users-reset-password');
        $requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'error');
        $requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'security');
        $requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'callback');
        $requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'access-profiles-update' && $request->getActionName() == 'put');
        $requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'ldap-sync');
        $requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'auto-join');
        $requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'maintenance' && $request->getActionName() == 'get');

        if ($requestingPublicResource == true) {
            return;
        }

        $hasUserIdAndToken = (($request->getHeader('userId', null) != null) && (($request->getHeader('token', null) != null)));
        //$hasUserIdAndToken = false;

        $authDataNs = new Zend_Session_Namespace('authData');

        if ($authDataNs->authData == null) {
            $hasUserIdAndToken = false;
        }

        //TODO handler for invalid token
        if ($hasUserIdAndToken) {
            return;
            /*
              $request->setParam('eror_msg', 'Invalid Token');
              $request->setModuleName('api')
              ->setControllerName('error')
              ->setActionName('index');
              return;
             * 
             */
        }

        if ((!$requestingPublicResource) && (!$hasUserIdAndToken)) {
            $request->setModuleName('api');
            $request->setControllerName('error');
            $request->setActionName('error');

            // Set up the error handler
            $error = new Zend_Controller_Plugin_ErrorHandler();
            $error->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
            $error->request = clone($request);
            $error->exception = new IMDT_Controller_Exception_InvalidToken('Session expired');
            $request->setParam('error_handler', $error);
        }
    }

}
