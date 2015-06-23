<?php
class Api_CategoriesController extends Zend_Rest_Controller{
    
    protected $_id;
    public $filters = array();
    
    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
        
        $this->_id = $this->_getParam('id', null);
        
        $this->model = new BBBManager_Model_MeetingRoomCategory();
        
        $this->select = $this->model->select()
                                    ->setIntegrityCheck(false)
                                    ->from(
                                        array(
                                            'mrc'=>'meeting_room_category'
                                        ),
                                        array(
                                            'meeting_room_category_id',
                                            'name',
                                            'parent_id'
                                        )
        );
        
        $this->columnValidators['name'] = array(new Zend_Validate_NotEmpty());
    }

    public function acessLog() {
    }
    
    public function deleteAction(){
        try {
            if(strpos($this->_id, ',') == -1) {
                $rowModel = $this->model->find($this->_id)->current();
                if($rowModel == null) throw new Exception(sprintf($this->_helper->translate('Category %s not found.'), $this->_id));
                
                //$name = $rowModel->name;
                $this->view->response = array('success'=>'1','msg'=>$this->_helper->translate('The category was deleted successfully.'));
                $rowModel->delete();
            } else {
                //$this->model->delete('meeting_room_id in ('.$this->_id.')');
                $collection = $this->model->find(explode(',',$this->_id));
                $i = 0;
                foreach($collection as $row) {
                    $row->delete();
                    $i++;
                }
                
                $this->view->response = array('success'=>'1','msg'=>sprintf($this->_helper->translate('%s categories was deleted successfully.'),$i));
            }
            
        } catch(Exception $e) {
            $this->view->response = array('success'=>'0','msg'=>$e->getMessage());
        }
    }
    
    public function getAction() {
        try{
            if($this->_id == 'leaf'){
                $rCollection = $this->model->getAllLeaf();

                $this->view->response = array('success'=>'1','collection'=>$rCollection,'msg'=>sprintf($this->_helper->translate('%s recordings retrieved successfully.'),count($rCollection)));
            }else{
                throw new Exception('Invalid argument');
            }
            
        } catch(Exception $e) {
            $this->view->response = array('success'=>'0','msg'=>$e->getMessage());
        }
    }
    
    public function headAction() {
        //$this->getResponse()->appendBody("From headAction()");
    }
    
    public function indexAction() {
        try {
            $this->select->order('name');
            
            IMDT_Util_ReportFilterHandler::parseThisFilters($this->select,$this->filters);
            IMDT_Util_ReportFilterHandler::parseThisQueries($this->select,$this->filters);
            
            $collection = $this->model->fetchAll($this->select);
            $rCollection = ($collection != null ? $collection->toArray() : array());
            
            $rCollectionOrderd = $this->model->getAllOrdered($rCollection);
            
            $this->view->response = array('success'=>'1','collection'=>$rCollectionOrderd,'msg'=>sprintf($this->_helper->translate('%s recordings retrieved successfully.'),count($rCollectionOrderd)));
        } catch(Exception $e) {
            $this->view->response = array('success'=>'0','msg'=>$e->getMessage());
        }
    }
    
    public function postAction() {
        $this->model->getAdapter()->beginTransaction();
        
        try {
            $data = $this->_helper->params();
            if(empty($data)) throw new Exception($this->_helper->translate('Invalid Request.'));
            
            $arrErrorMessages = $this->parseValidators($data);
            if(count($arrErrorMessages) > 0) {
                $this->view->response = array('success'=>'0','msg'=>$arrErrorMessages);
                return;
            }
            
            $rowModel = $this->model->createRow();
            $columns = $this->model->info('cols');
            $rowValidColumns = array_flip($columns);
            
            foreach($data as $field => $value) {
                if(isset($rowValidColumns[$field])) {
                    if(strlen($value) == 0) {
                        $rowModel->$field = null;
                    } else {
                        $rowModel->$field = $value;
                    }
                }
            }
            
            //$this->doConversions($rowModel);
            $newRowId = $rowModel->save();
            
            $this->model->getAdapter()->commit();
            $this->view->response = array('success'=>'1','msg'=>'','id'=>$newRowId,'msg'=>$this->_helper->translate('Category has been created successfully.'));
        } catch(Exception $e) {
            $this->view->response = array('success'=>'0','msg'=>$e->getMessage());
        }

    }

    public function putAction() {
        $this->model->getAdapter()->beginTransaction();
        
        try {
            if($this->_id == null) $this->forward('post');

            $data = $this->_helper->params();
            if(empty($data)) throw new Exception($this->_helper->translate('Invalid Request.'));
            
            
            if(!isset($data['name'])) unset($this->columnValidators['name']);
            $arrErrorMessages = $this->parseValidators($data);
            
            if(count($arrErrorMessages) > 0) {
                $this->view->response = array('success'=>'0','msg'=>$arrErrorMessages);
                return;
            }
            
            $rowModel = $this->model->find($this->_id)->current();
            if($rowModel == null) throw new Exception($this->_helper->translate(sprintf('Category %s not found.', $this->_id)));
            
            $columns = $this->model->info('cols');
            $rowValidColumns = array_flip($columns);
            
            foreach($data as $field => $value) {
                if(isset($rowValidColumns[$field])) {
                    if(strlen($value) == 0) {
                        $rowModel->$field = null;
                    } else {
                        $rowModel->$field = $value;
                    }
                }
            }
            
            $rowModel->save();
            
            $this->model->getAdapter()->commit();
            $this->view->response = array('success'=>'1','msg'=>$this->_helper->translate('Category has been successfully changed.'));
        } catch(Exception $e) {
            $this->model->getAdapter()->rollBack();
            $this->view->response = array('success'=>'0','msg'=>$e->getMessage());
        }
    }
    
    public function parseValidators($data) {
        $arrErrorMessages = array();
        
        foreach($this->columnValidators as $column=>$validators) {
            foreach($validators as $currValidator) {
                $value = $data[$column] ?: '';
                if(!$currValidator->isValid($value)) {
                    foreach($currValidator->getMessages() as $errorMessage) {
                        $arrErrorMessages[] = $this->_helper->translate('column-meeting_room-'.$column).': '.$errorMessage;
                    }
                    break;
                }
            }
        }
        
        return $arrErrorMessages;
    }
}