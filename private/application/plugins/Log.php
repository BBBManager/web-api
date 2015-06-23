<?php

class BBBManager_Plugin_Log extends Zend_Controller_Plugin_Abstract {

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
	if (IMDT_Util_Auth::getInstance()->get('id') == null) {
	    return;
	}

	try {
	    $params = $request->getUserParams();
	    $headers = (function_exists('apache_request_headers') ? apache_request_headers() : null);
	    
	    $logModel = new BBBManager_Model_AccessLog();
            
        $clientIpAddress = (($request->getHeader('clientIpAddress') != false) ? $request->getHeader('clientIpAddress') : $_SERVER['REMOTE_ADDR']);

	    $logModel->insert(array(
    		'user_id' => IMDT_Util_Auth::getInstance()->get('id'),
    		'ip_address' => $clientIpAddress,
    		'l_ip_address' => ip2long($clientIpAddress),
    		'create_date' => date('Y-m-d H:i:s'),
    		'uri' => sprintf('%s/%s/%s', $request->getModuleName(), $request->getControllerName(), $request->getActionName()),
    		'post' => (($params != null) ? Zend_Json::encode($params) : null),
    		'token' => IMDT_Util_Auth::getInstance()->get('token'),
    		'controller' => $request->getControllerName(),
    		'action' => $request->getActionName(),
    		'header' => ((($headers != null) && (count($headers) > 0)) ? Zend_Json::encode($headers) : null)
	    ));
	} catch (Exception $e) {
	    
	}
    }

}