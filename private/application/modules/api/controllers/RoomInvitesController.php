<?php

class Api_RoomInvitesController extends Zend_Rest_Controller {

    protected $_id;
    public $filters = array();

    public function init() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout()->disableLayout();
    
    	$this->_id = $this->_getParam('id', null);
    
    	$this->model = new BBBManager_Model_MeetingRoom();
    
    	$this->columnValidators = array();
    	$this->columnValidators['meeting_room_id'] = array(new Zend_Validate_NotEmpty(), new Zend_Validate_Int());
    	$this->columnValidators['subject'] = array(new Zend_Validate_NotEmpty());
    	$this->columnValidators['body'] = array(new Zend_Validate_NotEmpty());
    
    	$this->filters['meeting_room_id'] = array('column' => 'meeting_room_id', 'type' => 'int');
    
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
                $desc = $this->view->response['row']['last_invite_subject'].' ('.$this->view->response['row']['meeting_room_id'].')';
            }
            $this->view->response = null;
        }

        if(in_array($this->getRequest()->getActionName(),array('post','put'))) {
            $new = $this->_helper->params();
        }
        
        IMDT_Util_Log::write($desc,$new,$old);
    }
    
    public function rowHandler(&$row) {
        $row['_editable'] = '1';
        $row['_removable'] = '1';
    }

    public function deleteAction() {
	$this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

    public function getAction() {
	try {
	    $select = $this->model->select(array('meeting_room_id', 'last_invite_subject', 'last_invite_body'));
	    $select->where('meeting_room_id = ?', $this->_id);
	    $rowModel = $this->model->fetchRow($select);

	    if ($rowModel == null)
		throw new Exception(sprintf($this->_helper->translate('Meeting room %s not found.'), $this->_id));
	    $rowModel = $rowModel->toArray();
	    
        $this->rowHandler($rowModel);

	    $arrResponse = array();
	    $arrResponse['row'] = $rowModel;
	    $arrResponse['success'] = '1';
	    $arrResponse['msg'] = $this->_helper->translate('Meeting room retrieved successfully.');

	    $this->view->response = $arrResponse;
	} catch (Exception $e) {
	    $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
	}
    }

    public function headAction() {
	//$this->getResponse()->appendBody("From headAction()");
    }

    public function indexAction() {
	$this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

    public function parseValidators($data) {
	$arrErrorMessages = array();

	foreach ($this->columnValidators as $column => $validators) {
	    foreach ($validators as $currValidator) {
		$value = $data[$column] ? : '';
		if (!$currValidator->isValid($value)) {
		    foreach ($currValidator->getMessages() as $errorMessage) {
			$arrErrorMessages[] = $this->_helper->translate('column-meeting_room-' . $column) . ': ' . $errorMessage;
		    }
		}
	    }
	}

	return $arrErrorMessages;
    }

    public function doConversions(&$row) {
	
    }

    public function postAction() {
	$this->model->getAdapter()->beginTransaction();

	try {
	    $data = $this->_helper->params();
	    if (empty($data))
		throw new Exception($this->_helper->translate('Invalid Request.'));

	    $arrErrorMessages = $this->parseValidators($data);
	    if (count($arrErrorMessages) > 0) {
		$this->view->response = array('success' => '0', 'msg' => $arrErrorMessages);
		return;
	    }

	    $meetingRoomId = $data['meeting_room_id'];
            $arrTo = BBBManager_Util_MeetingRoom::getAllUsersEmail($meetingRoomId);
            
            if(count($arrTo) > IMDT_Util_Config::getInstance()->get('bbbmanager_send_invites_max_rcpt')){
                if((! isset($data['max_rcpt_confirmed'])) || ($data['max_rcpt_confirmed'] == '')){
                    $this->view->response = array('success' => '1', 'msg' => sprintf(IMDT_Util_Translate::_('This invitation will be sent to %s recipients, do you want to continue?'),count($arrTo)), 'data' => array('toCount' => count($arrTo)));
                    return;
                }
            }
            
	    $rowModel = $this->model->find($meetingRoomId)->current();
	    if ($rowModel == null)
		throw new Exception(sprintf($this->_helper->translate('Meeting room %s not found.'), $meetingRoomId));

	    $url = '<a href="' . IMDT_Util_Config::getInstance()->get('web_base_url') . $rowModel->url . '">' . IMDT_Util_Config::getInstance()->get('web_base_url') . $rowModel->url . '</a>';

	    $presenters = array();
	    $collectionGroupsPresenter = $this->model->getDefaultAdapter()->fetchAll('select g.group_id, g.name
                                                        from meeting_room_group
                                                        join `group` g on g.group_id = meeting_room_group.group_id
                                                        where meeting_room_group.meeting_room_profile_id = 3');
	    if (count($collectionGroupsPresenter) > 0) {
		foreach ($collectionGroupsPresenter as $curr) {
		    $presenters[] = $curr['name'];
		}
	    }

	    $collectionUsersPresenter = $this->model->getDefaultAdapter()->fetchAll('select u.user_id, u.name
                                                        from meeting_room_user
                                                        join `user` u on u.user_id = meeting_room_user.user_id
                                                        where meeting_room_user.meeting_room_profile_id = 3');
	    if (count($collectionUsersPresenter) > 0) {
		foreach ($collectionUsersPresenter as $curr) {
		    $presenters[] = $curr['name'];
		}
	    }

	    $presenters = implode(', ', $presenters);
            
            $body = $data['body'];
            $subject = $data['subject'];
            
            $tags = array(
                '__ROOM_START__'        => IMDT_Util_Date::filterDatetimeToCurrentLang($rowModel->date_start, false),
                '__ROOM_END__'          => IMDT_Util_Date::filterDatetimeToCurrentLang($rowModel->date_end, false),
                '__ROOM_NAME__'         => $rowModel->name,
                '__ROOM_URL__'          => $url,
                '__ROOM_PRESENTER__'    => $presenters
            );
            
            /*foreach($tags as $tag => $value){
                $body = str_replace($this->_helper->translate($tag), $tag, $body);
                $subject = str_replace($this->_helper->translate($tag), $tag, $subject);
            }*/
            
            foreach($tags as $tag => $value){
                $body = str_replace($tag, $value, $body);
                $subject = str_replace($tag, $value, $subject);
            }

	    /*$body = str_replace('__ROOM_START__', IMDT_Util_Date::filterDatetimeToCurrentLang($rowModel->date_start, false), $body);
	    $body = str_replace('__ROOM_END__', IMDT_Util_Date::filterDatetimeToCurrentLang($rowModel->date_end, false), $body);
	    $body = str_replace('__ROOM_NAME__', $rowModel->name, $body);
	    $body = str_replace('__ROOM_URL__', $url, $body);
	    $body = str_replace('__ROOM_PRESENTER__', $presenters, $body);

	    $subject = str_replace('__ROOM_START__', IMDT_Util_Date::filterDatetimeToCurrentLang($rowModel->date_start, false), $subject);
	    $subject = str_replace('__ROOM_END__', IMDT_Util_Date::filterDatetimeToCurrentLang($rowModel->date_end, false), $subject);
	    $subject = str_replace('__ROOM_NAME__', $rowModel->name, $subject);
	    $subject = str_replace('__ROOM_URL__', $url, $subject);
	    $subject = str_replace('__ROOM_PRESENTER__', $presenters, $subject);*/

	    $mail = new Zend_Mail('utf-8');
	    $mail->setBodyHtml($body);
	    //$mail->addCc($email)
	    $mail->setSubject($subject);

	    $defaultFrom = Zend_Mail::getDefaultFrom();
	    if($defaultFrom['name'] == null){
		$defaultFrom['name'] = $defaultFrom['email'];
	    }
	    
	    $mail->addTo($defaultFrom['email'], $defaultFrom['name']);
	    
	    foreach($arrTo as $email) {
	        $mail->addBcc($email);
	    }
	    
	    if (!$mail->send()) {
		//show_error($mail->print_debugger());
		throw new Exception('Erro ao enviar o e-mail');
	    }

	    $rowModel->last_invite_subject = $data['subject'];
	    $rowModel->last_invite_body = $data['body'];
	    $rowModel->save();

	    //$this->doConversions($rowModel);
	    //$rowModel->save();
        
	    $this->model->getAdapter()->commit();
	    $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('The invitation has been sent successfully.'));
	} catch (Exception $e) {
	    $this->model->getAdapter()->rollBack();
	    $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
	}
    }

    public function putAction() {
	$this->view->response = array('success' => '0', 'msg' => $this->_helper->translate('Disabled function.'));
    }

}