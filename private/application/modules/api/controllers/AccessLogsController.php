<?php

class Api_AccessLogsController extends Zend_Rest_Controller {

    protected $_id;
    public $filters = array();

    public function init() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout()->disableLayout();
    
    	$this->_id = $this->_getParam('id', null);
        
    	$this->model = new BBBManager_Model_AccessLog();
        
        $this->filters['create_date'] = array('column'=>'access_log.create_date','type'=>'datetime');
        $this->filters['ip_address'] = array('column'=>'access_log.ip_address','type'=>'ip_address');
        $this->filters['description'] = array('column'=>'access_log_description.description','type'=>'text');
        $this->filters['detail'] = array('column'=>'access_log.detail','type'=>'text');
        $this->filters['description_hash'] = array('column'=>array('access_log.controller','access_log.action'),'type'=>'hash');
        $this->filters['user'] = array('column'=>'access_log.user_id','type'=>'integer');
        $this->filters['user_auth_mode'] = array('column'=>'user.auth_mode_id','type'=>'integer');
        $this->filters['user_login'] = array('column'=>'user.login','type'=>'text');
        $this->filters['user_name'] = array('column'=>'user.name','type'=>'text');
        
        $this->select = $this->model->select()
                                    ->setIntegrityCheck(false)
                                    ->from('access_log')
                                    ->joinLeft('access_log_description','access_log_description.controller = access_log.controller and access_log_description.action = access_log.action',array('description'=>new Zend_Db_Expr('coalesce(access_log_description.description, concat(access_log.controller, " / ", access_log.action))')))
                                    ->join('user','user.user_id = access_log.user_id',array('user'=>'name'))
                                    ->order('access_log.create_date desc');
        
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
                $desc = $this->view->response['row']['access_log_id'];
            }
            $this->view->response = null;
        }

        if(in_array($this->getRequest()->getActionName(),array('post','put'))) {
            $new = $this->_helper->params();
        }
        
        IMDT_Util_Log::write($desc,$new,$old);
    }
    
    
    public function rowHandler(&$row) {
        //$row['meeting_room_action_name'] = IMDT_Util_Translate::_($row['meeting_room_action_name']);
    }

    public function getAction() {
    	try {
    	    $row = $this->model->find($this->_id)->current();
    	    if ($row == null)
    		throw new Exception(sprintf($this->_helper->translate('Log %s not found.'), $this->_id));
            
            $row = $row->toArray();
            $this->rowHandler($row);
            
    	    $this->view->response = array('success' => '1', 'row' => $row, 'msg' => $this->_helper->translate('Log retrieved successfully.'));
    	} catch (Exception $e) {
    	    $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
    	}
    }

    public function indexAction() {
    	try {
    	    IMDT_Util_ReportFilterHandler::parseThisFilters($this->select, $this->filters);
            IMDT_Util_ReportFilterHandler::parseThisQueries($this->select,$this->filters);
            
            
    	    $collection = $this->model->fetchAll($this->select);
    	    $rCollection = ($collection != null ? $collection->toArray() : array());
            
            array_walk($rCollection, array($this, 'rowHandler'));
            
    	    $this->view->response = array('success' => '1', 'collection' => $rCollection, 'msg' => $this->_helper->translate('%s logs retrieved successfully.', count($rCollection)));
    	} catch (Exception $e) {
    	    $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
    	}
    }

    public function deleteAction() {
    $this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }
    
    public function postAction() {
	$this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

    public function putAction() {
	$this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }
    
    public function headAction() {
    $this->getResponse()->appendBody("From headAction()");
    }

}