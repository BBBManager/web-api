<?php

class Api_PublicRoomsController extends Zend_Rest_Controller {

    protected $_model;
    protected $_id;

    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();

        $this->_model = new BBBManager_Model_MeetingRoom();
        $this->_id = $this->_getParam('id', null);
        //$this->acessLog();
    }

    public function acessLog() {
        $old = null;
        $new = null;
        $desc = '';

        IMDT_Util_Log::write($desc, $new, $old);
    }

    public function deleteAction() {
        
    }

    public function getAction() {
        if ($this->_id != null) {
            $this->forward('index');
        }
    }

    public function headAction() {
        
    }

    public function postAction() {
        
    }

    public function putAction() {
        
    }

    public function indexAction() {
        $roomStatus = $this->_getParam('status', null);
        $criteria = array('privacy_policy' => BBBManager_Config_Defines::$PUBLIC_MEETING_ROOM);

        if ($roomStatus != null) {
            $criteria['status'] = $roomStatus;
        }

        try {
            $myRoomsCollection = $this->_model->findMyRooms($this->_id, $criteria);

            $rCollection = ($myRoomsCollection != null ? $myRoomsCollection->toArray() : array());

            $this->view->response = array('success' => '1', 'collection' => $rCollection, 'msg' => sprintf($this->_helper->translate('%s meeting rooms retrieved successfully.'), count($rCollection)));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

}
