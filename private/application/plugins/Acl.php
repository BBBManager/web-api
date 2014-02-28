<?php

class BBBManager_Plugin_Acl extends Zend_Controller_Plugin_Abstract {

    public function preDispatch(Zend_Controller_Request_Abstract $request) {

	$requestingPublicResource = ($request->getModuleName() == 'api' && $request->getControllerName() == 'login');
	$requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'room-by-url');
	$requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'public-rooms' && $request->getActionName() == 'index');
	$requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'public-rooms' && $request->getActionName() == 'get');
	$requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'users-reset-password');
	$requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'error');
	$requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'security');
	$requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'callback');
	$requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'access-profiles-update' && $request->getActionName() == 'put');
	$requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'access-profiles-update' && $request->getActionName() == 'post');
	$requestingPublicResource = $requestingPublicResource || ($request->getModuleName() == 'api' && $request->getControllerName() == 'access-profiles-update' && $request->getActionName() == 'get');
	
	if ($requestingPublicResource == true) {
	    return;
	}

	$allowedByAcl = IMDT_Util_Acl::getInstance()->isAllowed(strtoupper($request->getControllerName()), strtoupper($request->getActionName()));

	if ($allowedByAcl == false) {
	    $request->setModuleName('api');
	    $request->setControllerName('error');
	    $request->setActionName('error');

	    // Set up the error handler
	    $error = new Zend_Controller_Plugin_ErrorHandler();
	    $error->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
	    $error->request = clone($request);
	    $error->exception = new IMDT_Controller_Exception_AccessDennied('You don\'t have access to the requested resource');
	    $request->setParam('error_handler', $error);
	}
    }

}