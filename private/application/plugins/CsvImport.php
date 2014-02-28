<?php
class BBBManager_Plugin_CsvImport extends Zend_Controller_Plugin_Abstract {

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
	//if ($request->getActionName() == 'post' && $request->getParam('import')) {
        if ($request->getParam('import', null) != null) {
	    $request->setActionName('import');
	}
    }
}