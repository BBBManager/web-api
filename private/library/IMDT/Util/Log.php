<?php

class IMDT_Util_Log {

    static public function write($desc = null, $newArray = null, $oldArray = null, $userId = null) {
        $controller = Zend_Controller_Front::getInstance();
        $request = $controller->getRequest();
        $params = $request->getUserParams();

        //$post = $request->getPost()

        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);

        $headers = (function_exists('apache_request_headers') ? apache_request_headers() : null);
        $headers = $_SERVER;

        $logModel = new BBBManager_Model_AccessLog();

        $clientIpAddress = (($request->getHeader('clientIpAddress') != false) ? $request->getHeader('clientIpAddress') : $_SERVER['REMOTE_ADDR']);

        //Dont log internal maintenance mode check calls
        if ($request->getControllerName() == 'maintenance' && $request->getActionName() == 'get') {
            return;
        }

        $logModel->insert(array(
            'user_id' => (IMDT_Util_Auth::getInstance()->get('id') != null ? IMDT_Util_Auth::getInstance()->get('id') : $userId),
            'ip_address' => $clientIpAddress,
            'l_ip_address' => ip2long($clientIpAddress),
            'create_date' => date('Y-m-d H:i:s'),
            'uri' => sprintf('%s/%s/%s', $request->getModuleName(), $request->getControllerName(), $request->getActionName()),
            'post' => (($params != null) ? Zend_Json::encode($params) : null),
            'token' => IMDT_Util_Auth::getInstance()->get('token'),
            'controller' => $request->getControllerName(),
            'action' => $request->getActionName(),
            'header' => ((($headers != null) && (count($headers) > 0)) ? Zend_Json::encode($headers) : null),
            'detail' => (($desc != null) ? $desc : null),
            'old' => (($oldArray != null) ? Zend_Json::encode($oldArray) : null),
            'new' => (($newArray != null) ? Zend_Json::encode($newArray) : null),
        ));
    }

}
