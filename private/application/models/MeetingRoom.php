<?php

class BBBManager_Model_MeetingRoom extends Zend_Db_Table_Abstract {

    protected $_name = 'meeting_room';
    protected $_primary = 'meeting_room_id';
    protected $_dependentTables = array('BBBManager_Model_MeetingRoomLog',
	'BBBManager_Model_Record',
	'BBBManager_Model_MeetingRoomGroup',
	'BBBManager_Model_MeetingRoomUser');

    public function findAll() {
	$select = $this->select();
	$select->from(array($this->_name), array('meeting_room_id',
	    'name', 'timezone',
	    'encrypted',
	    'privacy_policy',
	    'user_id',
	    'create_date',
	    'url',
	    'participants_limit',
	    'last_update',
	    'date_start',
	    'date_end',
            'status' => $this->getSqlForStatus()));

	$select->order(array('name'));
	return $this->fetchAll($select);
    }
    
    public function getSqlForStatus(){
        return new Zend_Db_Expr('
            CASE 
                WHEN date_start > now()
                    THEN ' . BBBManager_Config_Defines::$ROOM_WAITING . '
                WHEN date_start < now() and date_end > now()
                    THEN ' . BBBManager_Config_Defines::$ROOM_OPENED . '
                ELSE
                    ' . BBBManager_Config_Defines::$ROOM_CLOSED . '
            END
        ');
    }

    public function findRoomById($id = 0) {

	$select = $this->select();
	$select->from(
		array(
	    $this->_name
		), array(
	    'meeting_room_id',
	    'name',
	    'timezone',
	    'encrypted',
	    'privacy_policy',
	    'user_id',
	    'create_date',
	    'url',
	    'participants_limit',
	    'last_update',
	    'date_start',
	    'date_end'
		)
	);

	$select->where('meeting_room_id=?', $id);

	return $this->fetchAll($select);
    }

    public function findMyRooms($roomId = null, $criteria = null) {
	
	$publicRoomsSelect = $this->select();
	$publicRoomsSelect->from(
            $this->_name, 
            array(
                'meeting_room_id',
                'user_profile' => new Zend_Db_Expr(BBBManager_Config_Defines::$ROOM_ATTENDEE_PROFILE),
                'group_profile' => new Zend_Db_Expr(BBBManager_Config_Defines::$ROOM_ATTENDEE_PROFILE)
            )
        );
        
	$publicRoomsSelect->where('privacy_policy = ?', BBBManager_Config_Defines::$PUBLIC_MEETING_ROOM);
	
	$loggedUsersRoomsSelect = $this->select();
	$loggedUsersRoomsSelect->from(
            $this->_name, 
            array(
                'meeting_room_id',
                'user_profile' => new Zend_Db_Expr(BBBManager_Config_Defines::$ROOM_ATTENDEE_PROFILE),
                'group_profile' => new Zend_Db_Expr(BBBManager_Config_Defines::$ROOM_ATTENDEE_PROFILE)
            )
        );
	$loggedUsersRoomsSelect->where('privacy_policy = ?', BBBManager_Config_Defines::$ANY_LOGGED_USER_MEETING_ROOM);
	
	$userHasGroup = false;
	
	if(is_array(IMDT_Util_Auth::getInstance()->get('groupIds')) && (count(IMDT_Util_Auth::getInstance()->get('groupIds')) > 0)){
	    $userHasGroup = true;
	}
	
	$onlyInvitedUsersRoomsSelect = $this->select()->setIntegrityCheck(false);
	$onlyInvitedUsersRoomsSelect->from(array('mr' => $this->_name), array('meeting_room_id'));
	$onlyInvitedUsersRoomsSelect->joinLeft(
		array(
		    'mru'   => 'meeting_room_user'
		),
		'mru.meeting_room_id = mr.meeting_room_id AND ' . $this->getDefaultAdapter()->quoteInto('mru.user_id = ?', IMDT_Util_Auth::getInstance()->get('id')),
		array(
                    'user_profile' => new Zend_Db_Expr('COALESCE(mru.meeting_room_profile_id,null)')
                )
	);
	$onlyInvitedUsersRoomsSelect->where('mr.privacy_policy = ?', BBBManager_Config_Defines::$ONLY_INVITED_USERS_MEETING_ROOM);
	
	if($userHasGroup){
	    $onlyInvitedUsersRoomsSelect->joinLeft(
		array(
		    'mrg'   => 'meeting_room_group'
		),
		'mrg.meeting_room_id = mr.meeting_room_id AND ' . $this->getDefaultAdapter()->quoteInto('mrg.group_id in(?)', IMDT_Util_Auth::getInstance()->get('groupIds')),
		array(
                    'group_profile' => new Zend_Db_Expr('COALESCE(mrg.meeting_room_profile_id,null)')
                )
	    );
	}
	
	$myRoomsSelect = $publicRoomsSelect;
	
	if(IMDT_Util_Auth::getInstance()->get('id') != null){
	    $myRoomsSelect .= ' UNION ' . $loggedUsersRoomsSelect;
	    $myRoomsSelect .= ' UNION ' . $onlyInvitedUsersRoomsSelect;
	}
	
	/*$select = $this->select()->setIntegrityCheck(false);
	$select->from(
	    array(
		'mr' => new Zend_Db_Expr('(' . $myRoomsSelect . ')')
	    ), 
	    '*'
	);*/
	
	$innerSelect = $this->select()->setIntegrityCheck(false);
	$innerSelect->from(
	    array(
		'myrooms' => new Zend_Db_Expr('(' . $myRoomsSelect . ')')
	    ),
	    array(
		'meeting_room_id',
                'user_profile',
                'group_profile'
	    )
	);
	
	$innerSelect->join(
	    $this->_name,
	    'meeting_room.meeting_room_id = myrooms.meeting_room_id',
	    array(
		'name',
		'date_start',
		'date_end',
		'participants_limit',
		'record',
		'status' => new Zend_Db_Expr('
				CASE 
				    WHEN date_start > now()
					THEN ' . BBBManager_Config_Defines::$ROOM_WAITING . '
				    WHEN date_start < now() and date_end > now()
					THEN ' . BBBManager_Config_Defines::$ROOM_OPENED . '
				    ELSE
					' . BBBManager_Config_Defines::$ROOM_CLOSED . '
				END
			    '),
		'privacy_policy',
		'url',
                'meeting_mute_on_start',
                'meeting_lock_on_start',
                'lock_allow_moderator_locking',
                'lock_disable_mic_for_locked_users',
                'lock_disable_cam_for_locked_users',
                'lock_disable_public_chat_for_locked_users',
                'lock_disable_private_chat_for_locked_users'
	    )
	);
        
	$myRoomsDataSelect = $this->select()->setIntegrityCheck(false);
	$myRoomsDataSelect->from(
	    array(
		'mrd'	=> new Zend_Db_Expr('(' . $innerSelect . ')')
	    ),
	    '*'
	);
	
	
	
	$myRoomsDataSelect->joinLeft(
	    array(
		'mru' => 'meeting_room_user'
	    ),
	    'mru.meeting_room_id = mrd.meeting_room_id AND ' . $this->getDefaultAdapter()->quoteInto('mru.user_id = ?', IMDT_Util_Auth::getInstance()->get('id')),
	    /*array(
		'user_profile' => new Zend_Db_Expr('coalesce(mru.meeting_room_profile_id,' . BBBManager_Config_Defines::$SYSTEM_USER_PROFILE . ')')
	    )*/
            null
	);
	
	if($userHasGroup){
	    $myRoomsDataSelect->joinLeft(
		array(
		    'mrg' => 'meeting_room_group'
		),
		'mrg.meeting_room_id = mrd.meeting_room_id AND ' . $this->getDefaultAdapter()->quoteInto('mrg.group_id in (?)', IMDT_Util_Auth::getInstance()->get('groupIds')),
		/*array(
		    'group_profile' => new Zend_Db_Expr('coalesce(mrg.meeting_room_profile_id,' . BBBManager_Config_Defines::$SYSTEM_USER_PROFILE . ')')
		)*/
                null
	    );
	}
	
	$select = $this->select()
		->setIntegrityCheck(false)
		->from(
		array(
		    'mr' => new Zend_Db_Expr('(' . $myRoomsDataSelect . ')')
		), '*'
	);
	
	$select->joinLeft(
	    array(
		'mrg'=>'meeting_room_group'
	    ),
	    'mrg.meeting_room_id = mr.meeting_room_id', 
	    array(
		'group_admin_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 1 and mrg.auth_mode_id = 1 then mrg.group_id else null end SEPARATOR ',')"),
		'group_admin_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 1 and mrg.auth_mode_id = 2 then mrg.group_id else null end SEPARATOR ',')"),
		'group_moderator_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 2 and mrg.auth_mode_id = 1 then mrg.group_id else null end SEPARATOR ',')"),
		'group_moderator_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 2 and mrg.auth_mode_id = 2 then mrg.group_id else null end SEPARATOR ',')"),
		'group_presenter_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 3 and mrg.auth_mode_id = 1 then mrg.group_id else null end SEPARATOR ',')"),
		'group_presenter_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 3 and mrg.auth_mode_id = 2 then mrg.group_id else null end SEPARATOR ',')"),
		'group_attendee_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 4 and mrg.auth_mode_id = 1 then mrg.group_id else null end SEPARATOR ',')"),
		'group_attendee_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 4 and mrg.auth_mode_id = 2 then mrg.group_id else null end SEPARATOR ',')")
	));
	
	$select->joinLeft(
	    array(
		'mru'=>'meeting_room_user'
	    ),
	    'mru.meeting_room_id = mr.meeting_room_id', 
	    array(
		'user_admin_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 1 and mru.auth_mode_id = 1 then mru.user_id else null end SEPARATOR ',')"),
		'user_admin_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 1 and mru.auth_mode_id = 2 then mru.user_id else null end SEPARATOR ',')"),
		'user_moderator_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 2 and mru.auth_mode_id = 1 then mru.user_id else null end SEPARATOR ',')"),
		'user_moderator_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 2 and mru.auth_mode_id = 2 then mru.user_id else null end SEPARATOR ',')"),
		'user_presenter_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 3 and mru.auth_mode_id = 1 then mru.user_id else null end SEPARATOR ',')"),
		'user_presenter_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 3 and mru.auth_mode_id = 2 then mru.user_id else null end SEPARATOR ',')"),
		'user_attendee_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 4 and mru.auth_mode_id = 1 then mru.user_id else null end SEPARATOR ',')"),
		'user_attendee_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 4 and mru.auth_mode_id = 2 then mru.user_id else null end SEPARATOR ',')")
	    )
	);
	
	$rGroup = array(
	    'mr.meeting_room_id', 
	    'mr.name', 
	    'mr.date_start', 
	    'mr.date_end', 
	    'mr.participants_limit', 
	    'mr.record',
	    'mr.status',
	    'mr.privacy_policy', 
	    'mr.url',
	    'mr.user_profile',
            'mr.meeting_mute_on_start',
            'mr.meeting_lock_on_start',
            'mr.lock_allow_moderator_locking',
            'mr.lock_disable_mic_for_locked_users',
            'mr.lock_disable_cam_for_locked_users',
            'mr.lock_disable_public_chat_for_locked_users',
            'mr.lock_disable_private_chat_for_locked_users'
	);
	
	if($userHasGroup){
	    $rGroup[] = 'mr.group_profile';
	}
	
	$select->group($rGroup);
	
	if ($criteria != null) {
	    if (is_array($criteria)) {
		foreach ($criteria as $field => $value) {
		    if (is_array($value)) {
			$select->where('mr.' . $field . ' in (?)', $value);
		    } else {
			$select->where('mr.' . $field . ' = ?', $value);
		    }
		}
	    } elseif ($criteria instanceof Zend_Db_Select) {
		$criteria = $criteria->getPart(Zend_Db_Select::WHERE);
		foreach ($criteria as $criteriaItem) {
		    $select->where(preg_replace('/WHERE|AND/', '', $criteriaItem));
		}
	    }
	}
	
	if ($roomId != null) {
	    $select->where('mr.meeting_room_id = ?', $roomId);
	}
	
	if($userHasGroup == true){
	    $select->where('((user_profile is not null) or (group_profile is not null))');    
	}else{
	    $select->where('(user_profile is not null)');    
	}
	
	//die($select);
	
	return $this->fetchAll($select);
	
	/*
	$innerSelect = $this->select()
		->setIntegrityCheck(false)
		->from(array('mr'=>$this->_name), 
		    array(
			'meeting_room_id',
			'name',
			'date_start',
			'date_end',
			'participants_limit',
			'record',
			'status' => new Zend_Db_Expr('
					CASE 
					    WHEN date_start > now()
						THEN ' . BBBManager_Config_Defines::$ROOM_WAITING . '
					    WHEN date_start < now() and date_end > now()
						THEN ' . BBBManager_Config_Defines::$ROOM_OPENED . '
					    ELSE
						' . BBBManager_Config_Defines::$ROOM_CLOSED . '
					END
				    '),
			'privacy_policy',
			'url'
		    )
	)
    ->joinLeft(array('mrg'=>'meeting_room_group'), 'mrg.meeting_room_id = mr.meeting_room_id', 
                                                    array(
                                                           'group_admin_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 1 and mrg.auth_mode_id = 1 then mrg.group_id else null end SEPARATOR ',')"),
                                                           'group_admin_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 1 and mrg.auth_mode_id = 2 then mrg.group_id else null end SEPARATOR ',')"),
                                                           'group_moderator_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 2 and mrg.auth_mode_id = 1 then mrg.group_id else null end SEPARATOR ',')"),
                                                           'group_moderator_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 2 and mrg.auth_mode_id = 2 then mrg.group_id else null end SEPARATOR ',')"),
                                                           'group_presenter_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 3 and mrg.auth_mode_id = 1 then mrg.group_id else null end SEPARATOR ',')"),
                                                           'group_presenter_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 3 and mrg.auth_mode_id = 2 then mrg.group_id else null end SEPARATOR ',')"),
                                                           'group_attendee_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 4 and mrg.auth_mode_id = 1 then mrg.group_id else null end SEPARATOR ',')"),
                                                           'group_attendee_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mrg.meeting_room_profile_id = 4 and mrg.auth_mode_id = 2 then mrg.group_id else null end SEPARATOR ',')")
                                                         ))
    ->joinLeft(array('mru'=>'meeting_room_user'), 'mru.meeting_room_id = mr.meeting_room_id', 
                                                    array(
                                                            'user_admin_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 1 and mru.auth_mode_id = 1 then mru.user_id else null end SEPARATOR ',')"),
                                                            'user_admin_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 1 and mru.auth_mode_id = 2 then mru.user_id else null end SEPARATOR ',')"),
                                                            'user_moderator_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 2 and mru.auth_mode_id = 1 then mru.user_id else null end SEPARATOR ',')"),
                                                            'user_moderator_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 2 and mru.auth_mode_id = 2 then mru.user_id else null end SEPARATOR ',')"),
                                                            'user_presenter_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 3 and mru.auth_mode_id = 1 then mru.user_id else null end SEPARATOR ',')"),
                                                            'user_presenter_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 3 and mru.auth_mode_id = 2 then mru.user_id else null end SEPARATOR ',')"),
                                                            'user_attendee_local' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 4 and mru.auth_mode_id = 1 then mru.user_id else null end SEPARATOR ',')"),
                                                            'user_attendee_ldap' => new Zend_Db_Expr("GROUP_CONCAT(distinct case when mru.meeting_room_profile_id = 4 and mru.auth_mode_id = 2 then mru.user_id else null end SEPARATOR ',')")
                                                    ))
    ->group(array('mr.meeting_room_id', 'mr.name', 'mr.date_start', 'mr.date_end', 'mr.privacy_policy', 'mr.url', 'mr.participants_limit', 'mr.record'));
    
	$select = $this->select()
		->setIntegrityCheck(false)
		->from(
		array(
	    'mr' => new Zend_Db_Expr('(' . $innerSelect . ')')
		), '*'
	);

	if ($public == false) {
	    $select->joinLeft(
		    array(
		'mruser' => 'meeting_room_user',
		    ), 'mruser.meeting_room_id = mr.meeting_room_id', array(
		'user_profile' => 'meeting_room_profile_id'
		    )
	    );
	    
	    $select->where('mruser.user_id = ?', IMDT_Util_Auth::getInstance()->get('id'));

	    if (IMDT_Util_Auth::getInstance()->get('groupIds') != null) {
		$select->joinLeft(
			array(
		    'mrgroup' => 'meeting_room_group'
			), 'mrgroup.meeting_room_id = mr.meeting_room_id', array(
		    'group_profile' => 'meeting_room_profile_id'
			)
		);
		
		$select->where('mrgroup.group_id in (?)', IMDT_Util_Auth::getInstance()->get('groupIds'));
	    }
	} else {
	    $select->where('mr.privacy_policy in (?)', array(BBBManager_Config_Defines::$PUBLIC_MEETING_ROOM, BBBManager_Config_Defines::$ANY_LOGGED_USER_MEETING_ROOM));
	}

	if ($criteria != null) {
	    if (is_array($criteria)) {
		foreach ($criteria as $field => $value) {
		    if (is_array($value)) {
			$select->where('mr.' . $field . ' in (?)', $value);
		    } else {
			$select->where('mr.' . $field . ' = ?', $value);
		    }
		}
	    } elseif ($criteria instanceof Zend_Db_Select) {
		$criteria = $criteria->getPart(Zend_Db_Select::WHERE);
		foreach ($criteria as $criteriaItem) {
		    $select->where(preg_replace('/WHERE|AND/', '', $criteriaItem));
		}
	    }
	}

	if ($roomId != null) {
	    $select->where('mr.meeting_room_id = ?', $roomId);
	}
	
	return $this->fetchAll($select);*/
    }
    
    public function findRoomByUrl($roomUrl){
	$select = $this->select();
	$select->where('url = ?', $roomUrl);
	
	return $this->fetchRow($select);
    }
    
    public function findRoomMembers($meetingRoomId){
	$select = $this->select()->distinct();
	$select->setIntegrityCheck(false);
	$select->from(array('mr' => 'meeting_room'), array('meeting_room_id'));
	$select->joinLeft(array('mru' => 'meeting_room_user'), 'mru.meeting_room_id = mr.meeting_room_id', null);
	$select->joinLeft(array('u' => 'user'), 'u.user_id = mru.user_id', array('user_id', 'email'));
	$select->joinLeft(array('mrg' => 'meeting_room_group'), 'mrg.meeting_room_id = mr.meeting_room_id', null);
	$select->joinLeft(array('g' => 'group'), 'g.group_id = mrg.group_id', array('group_id', 'group_name' => 'name', 'group_auth_mode_id' => 'auth_mode_id'));
	$select->joinLeft(array('ug' => 'user_group'), 'ug.group_id = g.group_id and g.auth_mode_id = ' . BBBManager_Config_Defines::$LOCAL_AUTH_MODE, array('user_id_from_group' => 'user_id'));
	$select->joinLeft(array('um' => 'user'), 'um.user_id = ug.user_id' , array('user_email_from_group' => 'email'));
	
	$select->where('mr.meeting_room_id = ?', $meetingRoomId);
	
	return $this->fetchAll($select);
    }
    
    public function findModeratorsAndPresenters($meetingRoomId){
        $select = $this->select()->setIntegrityCheck(false);
        $select->from('meeting_room_user', array('meeting_room_profile_id'));
        $select->joinLeft('user', 'user.user_id = meeting_room_user.user_id',array('user_id', 'name'));
        
        $select->where('meeting_room_user.meeting_room_id = ?', $meetingRoomId);
        $select->where('meeting_room_user.meeting_room_profile_id in(?)', array(BBBManager_Config_Defines::$ROOM_MODERATOR_PROFILE, BBBManager_Config_Defines::$ROOM_PRESENTER_PROFILE));
        $select->order(array('meeting_room_profile_id'));
        
        return $this->fetchAll($select);
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
                
                if(in_array($column, array($pk)) !== false){
                    continue;
                }
                
		if(array_search($column, $cols) !== false){
                    if($value == ''){
                        $value = NULL;
                    }else{
                        $validRecord[$column] = $value;    
                    }
		}
	    }
            
	    $validRecords[] = $validRecord;
	}
	
	$recordCount = 0;
	
	foreach($validRecords as $iRecord => $record){
            $existsSelect = $this->select();
            //$existsSelect->where(new Zend_Db_Expr('(' . $this->getAdapter()->quoteInto('(login = ?)', $record['login']) . ' or ' . $this->getAdapter()->quoteInto('(email = ?)', $record['email']) . ')'));
            $existsSelect->where('url = ?', $record['url']);
            $existsSelect->where(BBBManager_Config_Defines::$ROOM_CLOSED . ' = ?', $this->getSqlForStatus());
            
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