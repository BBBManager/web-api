<?php

class BBBManager_Model_User extends Zend_Db_Table_Abstract {

    protected $_name = 'user';
    protected $_primary = 'user_id';
    protected $_dependentTables = array('BBBManager_Model_UserGroup',
	'BBBManager_Model_MeetingRoomUser',
	'BBBManager_Model_MeetingRoomLog',
	'BBBManager_Model_RecordUser',
	'BBBManager_Model_InviteTemplate');
    protected $_referenceMap = array(
	'AuthMode' => array(
	    'columns' => 'auth_mode_id',
	    'refTableClass' => 'BBBManager_Model_AuthMode',
	    'refColumns' => 'auth_mode_id',
	    'onDelete' => self::CASCADE_RECURSE,
	    'onUpdate' => self::CASCADE_RECURSE
	),
	'AccessProfile' => array(
	    'columns' => 'access_profile_id',
	    'refTableClass' => 'BBBManager_Model_AccessProfile',
	    'refColumns' => 'access_profile_id',
	    'onDelete' => self::CASCADE_RECURSE,
	    'onUpdate' => self::CASCADE_RECURSE
	)
    );
    
    public function findAll() {
	$select = $this->select()
		->setIntegrityCheck(false)
		->from($this->_name)
		->join('user_profile', 'user_profile.user_profile_id = user.user_profile_id', array('profile_name' => 'name'))
		->order(array('user.user_id'));

	return $this->fetchAll($select);
    }

    public function findByEmail($email) {
	$select = $this->select()
		->from($this->_name)
		->where('email = ?', $email);
	return $this->fetchRow($select);
    }

    public function findIdAndName($groupId = null) {
	$select = $this->select();
	if ($groupId != null) {
	    $select->setIntegrityCheck(false);
	    $select->from($this->_name, array('user_id', 'name'));
	    $select->joinLeft('user_group', 'user_group.user_id = user.user_id and user_group.group_id = ' . $groupId, array('Linked' => new Zend_Db_Expr('case when user_group.group_id is null then false else true end')));
	    //$select->where('user_group.group_id = ?', $groupId);
	} else {
	    $select->from($this->_name, array('user_id', 'name', 'Linked' => new Zend_Db_Expr('false')));
	}

	$select->order(array('name'));

	return $this->fetchAll($select);
    }

    public function findUserByAuth($auth = null, $roomId = null, $evId = null) {
	$select = $this->select();
	if (is_null($roomId) && is_null($evId)) {
	    $select->from(array($this->_name), array('user_id',
		'name',
		'auth_mode_id',
		'linkedRoom' => new Zend_Db_Expr('false'),
		'meeting_room_profile_id' => new Zend_Db_Expr('0'),
		'recordUser' => new Zend_Db_Expr('false')));
	    $select->where('auth_mode_id =?', $auth);
	} else if (!is_null($roomId)) {

	    $select->setIntegrityCheck(false);
	    $select->from($this->_name, array('user_id', 'name', 'auth_mode_id'));
	    $select->joinLeft(array('meeting_room_user'), 'meeting_room_user.user_id = user.user_id AND meeting_room_user.meeting_room_Id=' . $roomId, array('meeting_room_profile_id', 'linkedRoom' => new Zend_Db_Expr('CASE WHEN meeting_room_user.user_id IS NULL THEN false ELSE true END')));
	    $select->where('auth_mode_id =?', $auth);
	} else if (!is_null($evId)) {
	    $select->setIntegrityCheck(false);
	    $select->from($this->_name, array('user_id', 'name', 'auth_mode_id'));
	    $select->joinLeft(array('record_user'), 'record_user.user_id=user.user_id AND record_user.record_id=' . $evId, array('recordUser' => new Zend_Db_expr('CASE WHEN record_user.user_id IS NULL THEN false ELSE true END')));
	    $select->where('auth_mode_id=?', $auth);
	}

	return $this->fetchAll($select);
    }

    public function sendNewPassword($user) {
	try {
	    $newPassword = IMDT_Util_Password::generate();

	    $view = new Zend_View();
	    $view->setScriptPath(APPLICATION_PATH);

	    $view->title = 'Esqueci minha senha';

	    $emailPrepend = $view->render('views/scripts/layout/email-start.phtml');
	    $emailAppend = $view->render('views/scripts/layout/email-end.phtml');
	    
	    $greeting = $view->translate('Hello');
	    $greeting .= ' ' . $user->name;
	    
	    $message = $view->translate('This is an automatic message, use the information below to access the system') . '.';
	    $message .= '<table>';
	    $message .= '<tr>';
	    $message .= sprintf('<th align="right">%s:</th><td><a href="%s">%s</a></td>', $view->translate('System Url'), IMDT_Util_Config::getInstance()->get('web_base_url'), IMDT_Util_Config::getInstance()->get('web_base_url'));
	    $message .= '</tr>';
	    $message .= '<tr>';
	    $message .= sprintf('<th align="right">%s:</th><td>%s</td>', $view->translate('Username'), $user->login);
	    $message .= '</tr>';
	    $message .= '<tr>';
	    $message .= sprintf('<th align="right">%s:</th><td>%s</td>', $view->translate('Password'), $newPassword);
	    $message .= '</tr>';
	    $message .= '</table>';

	    $emailBody = $view->partial('views/emails/forget-pass.phtml', array('greeting' => $greeting, 'message' =>  $message));
        
            $defaultSubjectName = '';

            if(BBBManager_Util_Skinning::getInstance()->get('company_name') != null){
                $defaultSubjectName = BBBManager_Util_Skinning::getInstance()->get('company_name');
            }

            if(BBBManager_Util_Skinning::getInstance()->get('system_title') != null){
                $defaultSubjectName .= ' ' . BBBManager_Util_Skinning::getInstance()->get('system_title');
            }
        
	    $mail = new Zend_Mail('utf-8');
	    $mail->addTo($user->email)
		 ->addBcc('diogo@imdt.com.br')
		 ->setSubject($defaultSubjectName . ' - ' . $view->translate('New Password'))
		 ->setBodyHtml($emailPrepend . $emailBody . $emailAppend);
	    $this->update(array('password' => IMDT_Util_Hash::generate($newPassword)), $this->getAdapter()->quoteInto('user_id = ?', $user->user_id));

	    $mail->send();
	    return true;
	} catch (Exception $e) {
	    throw new Exception($e);
	}
    }

    public function findByLogin($login) {
	$select = $this->select();
	$select->where('login = ?', $login);

	return $this->fetchRow($select);
    }
    
    public function getSqlStatementForActiveUsers(){
        return 'CASE 
                    WHEN valid_from is null and valid_to is null
                        THEN 1
                    WHEN valid_from is not null and valid_from <= CURRENT_DATE and valid_to is null
                        THEN 1
                    WHEN valid_to is not null and valid_to >= CURRENT_DATE and valid_from is null
                        THEN 1
                    WHEN valid_from is not null and valid_from <= CURRENT_DATE and valid_to is not null and valid_to >= CURRENT_DATE
                        THEN 1
                    ELSE
                        0
                END';
    }
    
    public function importCsv($fileContents){
        $csvRecords = IMDT_Util_Csv::import($fileContents);
	$cols = $this->info('cols');
        $pk = (is_array($this->_primary) ? current($this->_primary) : $this->_primary);
        
	$validRecords = array();
	
	foreach($csvRecords as $record){
	    $validRecord = array();
	    foreach($record as $column => $value){
                /*if($column == $pk){
                    continue;
                }*/
                
                if(in_array($column, array($pk, 'auth_mode_id', 'access_profile_id')) !== false){
                    continue;
                }
                
		if(array_search($column, $cols) !== false){
                    if($value == ''){
                        $value = NULL;
                    }elseif($column == 'name'){
                        if(mb_detect_encoding($value) == 'UTF-8'){
                            $value = utf8_decode($value);
                        }
                        $validRecord[$column] = IMDT_Util_String::camelize($value);
                    }else{
                        $validRecord[$column] = $value;    
                    }
		}
	    }
            
            $validRecord['auth_mode_id'] = BBBManager_Config_Defines::$LOCAL_AUTH_MODE;
            $validRecord['access_profile_id'] = BBBManager_Config_Defines::$SYSTEM_USER_PROFILE;
            
	    $validRecords[] = $validRecord;
	}
	
	$recordCount = 0;
	
	foreach($validRecords as $iRecord => $record){
            $existsSelect = $this->select();
            $existsSelect->where(new Zend_Db_Expr('(' . $this->getAdapter()->quoteInto('(login = ?)', $record['login']) . ' or ' . $this->getAdapter()->quoteInto('(email = ?)', $record['email']) . ')'));
            
            $exists = $this->fetchRow($existsSelect);
            
            if($exists != null){
                throw new Exception(sprintf(IMDT_Util_Translate::_('Invalid CSV file, record in line %s already exists.'), ($iRecord + 1)));
            }
            
            $this->insert($record);
	    $recordCount++;
	}
	
	return $recordCount;
    }
}