<?php

class Api_BbbConfigsController extends Zend_Rest_Controller {

    protected $_id;
    public $filters = array();

    public function init() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout()->disableLayout();
    
    	$this->_id = $this->_getParam('id', null);
    
    	//$this->columnValidators = array();
    	//$this->columnValidators['name'] = array(new Zend_Validate_NotEmpty());
    
    	//$this->filters['name'] = array('column' => 'name', 'type' => 'text');
    	//$this->filters['source_ip'] = array('column'=>'network','type'=>'text');
    	//TODO source-Ip
        $this->acessLog();
    }

    public function acessLog() {
        $old = null;
        $new = null;
        $desc = '';
        if($this->_getParam('id', false)) {
            $this->getAction();
            if($this->view->response['success'] == '1') {
                if(in_array($this->getRequest()->getActionName(),array('delete','put'))) {
                    $old = $this->view->response['row'];
                }
                $desc = $this->view->response['row']['name'].' ('.$this->view->response['row']['access_profile_id'].')';
            }
            $this->view->response = null;
        }
        
        if(in_array($this->getRequest()->getActionName(),array('post','put'))) {
            $new = $this->_helper->params();
        }
        
        IMDT_Util_Log::write($desc,$new,$old);
    }
    
    public function rowHandler(&$row) {
        $row['_editable'] = '0';
        $row['_removable'] = '0';
    }
    
    public function deleteAction() { }

    public function getAction() { }

    public function headAction() {
	//$this->getResponse()->appendBody("From headAction()");
    }

    public function indexAction() {
	try {
        
	    $arrTimezones = array();
        $bbbIniParamsFile = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'bbb.ini';
        
        $rCollection = array();
        
        if(file_exists($bbbIniParamsFile)) {
            $bbbIniConfig = new Zend_Config_Ini($bbbIniParamsFile);
            //debug($bbbIniConfig->get('meetingLockOnStart'));
            $rCollection['meetingMuteOnStart'] = $bbbIniConfig->get('meetingMuteOnStart') ?: '0'; //if not exists, false! 
            $rCollection['meetingLockOnStart'] = $bbbIniConfig->get('meetingLockOnStart') ?: '0'; //if not exists, false! 
            $rCollection['lockAllowModeratorLocking'] = $bbbIniConfig->get('lockAllowModeratorLocking') ?: '0'; //if not exists, false! 
            $rCollection['lockDisableMicForLockedUsers'] = $bbbIniConfig->get('lockDisableMicForLockedUsers') ?: '0'; //if not exists, false! 
            $rCollection['lockDisableCamForLockedUsers'] = $bbbIniConfig->get('lockDisableCamForLockedUsers') ?: '0'; //if not exists, false! 
            $rCollection['lockDisablePublicChatForLockedUsers'] = $bbbIniConfig->get('lockDisablePublicChatForLockedUsers') ?: '0'; //if not exists, false! 
            $rCollection['lockDisablePrivateChatForLockedUsers'] = $bbbIniConfig->get('lockDisablePrivateChatForLockedUsers') ?: '0'; //if not exists, false! 
            
            //$timezones = $bbbIniConfig->toArray();
            //$arrTimezones = $timezones['timezones'];
        } else {
            $rCollection['meetingMuteOnStart'] = '0';
            $rCollection['meetingLockOnStart'] = '0';
            $rCollection['lockAllowModeratorLocking'] = '0';
            $rCollection['lockDisableMicForLockedUsers'] = '0';
            $rCollection['lockDisableCamForLockedUsers'] = '0';
            $rCollection['lockDisablePublicChatForLockedUsers'] = '0';
            $rCollection['lockDisablePrivateChatForLockedUsers'] = '0';
        }
        /*
        $i = 0;
        $rCollection = array();
        foreach($arrTimezones as $curr) {
            list($id,$name) = explode(',',$curr);
            $rCollection[$name] = array('id'=>$id,'name'=>$name,'default'=>($i++ == 0));
        }
        
        ksort($rCollection);
        
        array_walk($rCollection, array($this, 'rowHandler'));
        */
	    $this->view->response = array('success' => '1', 'collection' => $rCollection, 'msg' => sprintf($this->_helper->translate('%s timezones retrieved successfully.'), count($rCollection)));
	} catch (Exception $e) {
	    $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
	}
    }

    public function parseValidators($data) {
	$arrErrorMessages = array();

	foreach ($this->columnValidators as $column => $validators) {
	    foreach ($validators as $currValidator) {
		$value = (isset($data[$column]) && strlen($data[$column]) > 0) ? $data[$column] : '';
		if (!$currValidator->isValid($value)) {
		    foreach ($currValidator->getMessages() as $errorMessage) {
			 $arrErrorMessages[] = $this->_helper->translate('column-access profile-' . $column) . ': ' . $errorMessage;
		    }
            break;
		}
	    }
	}

	return $arrErrorMessages;
    }

    public function doConversions(&$row) {
	
    }
    
    public function postAction() { }

    public function putAction() { }

}