<?php
class Api_RoomsController extends Zend_Rest_Controller{
    
    protected $_id;
    public $filters = array();
    
    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
        
        $this->_id = $this->_getParam('id', null);
        
        $this->model = new BBBManager_Model_MeetingRoom();
        
        $this->select = $this->model->select()
                                    ->setIntegrityCheck(false)
                                    ->from(
                                        array(
                                            'mr'=>'meeting_room'
                                        ),
                                        array(
                                            'meeting_room_id',
                                            'name',
                                            'date_start',
                                            'date_end',
                                            'timezone',
                                            'encrypted',
                                            'privacy_policy',
                                            'url',
                                            'observations',
                                            'participants_limit',
                                            'record',
                                            'meeting_mute_on_start',
                                            'meeting_lock_on_start',
                                            'lock_disable_mic_for_locked_users',
                                            'lock_disable_cam_for_locked_users',
                                            'lock_disable_public_chat_for_locked_users',
                                            'lock_disable_private_chat_for_locked_users',
                                            'lock_layout_for_locked_users',
                                            'user_id',
                                            'create_date',
                                            'last_update',
                                            'status' => $this->model->getSqlForStatus(),
                                            'recordings_count' => $this->model->getSqlForRecordingsCount('mr'),
                                            'meeting_room_category_id'))
                                    ->joinLeft(array('mrc'=>'meeting_room_category'), 'mrc.meeting_room_category_id = mr.meeting_room_category_id', 
                                                                    array(
                                                                           'meeting_room_category_name' => 'mrc.name'
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
                                    ->joinLeft(
                                            array(
                                                'recordings' => new Zend_Db_Expr(
                                                        '( 
                                                            select
                                                                meeting_room.meeting_room_id,
                                                                count(record.record_id) > 0 as has_recordings
                                                            from
                                                                meeting_room
                                                            left outer join 
                                                                record
                                                                    on record.meeting_room_id = meeting_room.meeting_room_id
                                                            group by meeting_room.meeting_room_id
                                                        )'
                                                )
                                            ),
                                            'recordings.meeting_room_id = mr.meeting_room_id',
                                            array(
                                                'has_recordings'
                                            )

                                    )
                
                                    ->group(array('mr.meeting_room_id', 'mr.name', 'mr.date_start', 'mr.date_end', 'mr.timezone', 'mr.encrypted', 'mr.privacy_policy', 'mr.url', 'mr.participants_limit', 'mr.record', 'mr.user_id', 'mr.create_date', 'mr.last_update'));
        
        
        
        
        $this->columnValidators = array();
        $this->columnValidators['main_date_start'] = array(new Zend_Validate_NotEmpty(), 
                                                    new Zend_Validate_Date(array('format' => 'yyyy-MM-dd HH:mm:ss')));
                                                    
        $this->columnValidators['date_start'] = array(new Zend_Validate_NotEmpty(), 
                                                    new Zend_Validate_Date(array('format' => 'yyyy-MM-dd HH:mm:ss')));
                                                    
        $this->columnValidators['date_end'] = array(new Zend_Validate_NotEmpty(), 
                                                    new Zend_Validate_Date(array('format' => 'yyyy-MM-dd HH:mm:ss')));
                                                    
        $this->columnValidators['name'] = array(new Zend_Validate_NotEmpty());
        
        $this->columnValidators['participants_limit'] = array(new Zend_Validate_NotEmpty(), new Zend_Validate_Int());
        
        /*
        $this->columnValidators['meeting_mute_on_start'] = array(new Zend_Validate_Int());
        $this->columnValidators['meeting_lock_on_start'] = array(new Zend_Validate_Int());
        $this->columnValidators['lock_disable_mic_for_locked_users'] = array(new Zend_Validate_Int());
        $this->columnValidators['lock_disable_cam_for_locked_users'] = array(new Zend_Validate_Int());
        $this->columnValidators['lock_disable_public_chat_for_locked_users'] = array(new Zend_Validate_Int());
        $this->columnValidators['lock_disable_private_chat_for_locked_users'] = array(new Zend_Validate_Int());
        */
        
        $optUkExclude = new Zend_Db_Expr("date_end > current_timestamp");
        if($this->_id != null) {
            $optUkExclude = new Zend_Db_Expr("date_end > current_timestamp and meeting_room_id != ".$this->_id);
        }
        
        $this->columnValidators['url'] = array(new Zend_Validate_NotEmpty(), 
                                                new Zend_Validate_Regex('/^[\w-]*$/'),
                                                new Zend_Validate_Db_NoRecordExists(
                                                array(
                                                    'table' => 'meeting_room',
                                                    'field' => 'url',
                                                    'exclude' => $optUkExclude
                                                )));
        
        $this->filters['name'] = array('column'=>'name','type'=>'text');
        $this->filters['date_start'] = array('column'=>'date_start','type'=>'datetime');
        $this->filters['date_end'] = array('column'=>'date_end','type'=>'datetime');
        $this->filters['timezone'] = array('column'=>'timezone','type'=>'integer');
        $this->filters['privacy_policy'] = array('column'=>'privacy_policy','type'=>'integer');
        $this->filters['url'] = array('column'=>'url','type'=>'text');
        $this->filters['participants_limit'] = array('column'=>'participants_limit','type'=>'integer');
        $this->filters['recordings_count'] = array('column'=>'recordings_count','type'=>'integer');
        $this->filters['has_recordings'] = array('column'=>'has_recordings','type'=>'boolean');
        $this->filters['meeting_room_category_id'] = array('column'=>'meeting_room_category_id','type'=>'integer');
        
        $this->filters['encrypted'] = array('column'=>'encrypted','type'=>'boolean');
        $this->filters['record'] = array('column'=>'record','type'=>'boolean');
        $this->filters['meeting_mute_on_start'] = array('column'=>'meeting_mute_on_start','type'=>'boolean');
        $this->filters['meeting_lock_on_start'] = array('column'=>'meeting_lock_on_start','type'=>'boolean');
        $this->filters['lock_disable_cam_for_locked_users'] = array('column'=>'lock_disable_cam_for_locked_users','type'=>'boolean');
        $this->filters['lock_disable_public_chat_for_locked_users'] = array('column'=>'lock_disable_public_chat_for_locked_users','type'=>'boolean');
        $this->filters['lock_disable_private_chat_for_locked_users'] = array('column'=>'lock_disable_private_chat_for_locked_users','type'=>'boolean');
        
        $select = 'select 1 from meeting_room_group join `group` using(group_id) where meeting_room_group.meeting_room_id = mr.meeting_room_id and meeting_room_group.meeting_room_profile_id = 1';
        $this->filters['group_admin'] = array('column'=>'meeting_room_group.group_id','type'=>'exists','select'=>$select);
        $this->filters['group_admin_auth_mode'] = array('column'=>'`group`.auth_mode_id','type'=>'exists_key','select'=>$select);
        $this->filters['group_admin_name'] = array('column'=>'`group`.name','type'=>'exists_text','select'=>$select);
        $select = 'select 1 from meeting_room_group join `group` using(group_id) where meeting_room_group.meeting_room_id = mr.meeting_room_id and meeting_room_group.meeting_room_profile_id = 2';
        $this->filters['group_moderator'] = array('column'=>'meeting_room_group.group_id','type'=>'exists','select'=>$select);
        $this->filters['group_moderator_auth_mode'] = array('column'=>'`group`.auth_mode_id','type'=>'exists_key','select'=>$select);
        $this->filters['group_moderator_name'] = array('column'=>'`group`.name','type'=>'exists_text','select'=>$select);
        $select = 'select 1 from meeting_room_group join `group` using(group_id) where meeting_room_group.meeting_room_id = mr.meeting_room_id and meeting_room_group.meeting_room_profile_id = 3';
        $this->filters['group_presenter'] = array('column'=>'meeting_room_group.group_id','type'=>'exists','select'=>$select);
        $this->filters['group_presenter_auth_mode'] = array('column'=>'`group`.auth_mode_id','type'=>'exists_key','select'=>$select);
        $this->filters['group_presenter_name'] = array('column'=>'`group`.name','type'=>'exists_text','select'=>$select);
        $select = 'select 1 from meeting_room_group join `group` using(group_id) where meeting_room_group.meeting_room_id = mr.meeting_room_id and meeting_room_group.meeting_room_profile_id = 4';
        $this->filters['group_attendee'] = array('column'=>'meeting_room_group.group_id','type'=>'exists','select'=>$select);
        $this->filters['group_attendee_auth_mode'] = array('column'=>'`group`.auth_mode_id','type'=>'exists_key','select'=>$select);
        $this->filters['group_attendee_name'] = array('column'=>'`group`.name','type'=>'exists_text','select'=>$select);
        
        $select = 'select 1 from meeting_room_user join user using(user_id) where meeting_room_user.meeting_room_id = mr.meeting_room_id and meeting_room_user.meeting_room_profile_id = 1';
        $this->filters['user_admin'] = array('column'=>'meeting_room_user.user_id','type'=>'exists','select'=>$select);
        $this->filters['user_admin_auth_mode'] = array('column'=>'user.auth_mode_id','type'=>'exists_key','select'=>$select);
        $this->filters['user_admin_login'] = array('column'=>'user.login','type'=>'exists_text','select'=>$select);
        $this->filters['user_admin_name'] = array('column'=>'user.name','type'=>'exists_text','select'=>$select);
        $select = 'select 1 from meeting_room_user join user using(user_id) where meeting_room_user.meeting_room_id = mr.meeting_room_id and meeting_room_user.meeting_room_profile_id = 2';
        $this->filters['user_moderator'] = array('column'=>'meeting_room_user.user_id','type'=>'exists','select'=>$select);
        $this->filters['user_moderator_auth_mode'] = array('column'=>'user.auth_mode_id','type'=>'exists_key','select'=>$select);
        $this->filters['user_moderator_login'] = array('column'=>'user.login','type'=>'exists_text','select'=>$select);
        $this->filters['user_moderator_name'] = array('column'=>'user.name','type'=>'exists_text','select'=>$select);
        $select = 'select 1 from meeting_room_user join user using(user_id) where meeting_room_user.meeting_room_id = mr.meeting_room_id and meeting_room_user.meeting_room_profile_id = 3';
        $this->filters['user_presenter'] = array('column'=>'meeting_room_user.user_id','type'=>'exists','select'=>$select);
        $this->filters['user_presenter_auth_mode'] = array('column'=>'user.auth_mode_id','type'=>'exists_key','select'=>$select);
        $this->filters['user_presenter_login'] = array('column'=>'user.login','type'=>'exists_text','select'=>$select);
        $this->filters['user_presenter_name'] = array('column'=>'user.name','type'=>'exists_text','select'=>$select);
        $select = 'select 1 from meeting_room_user join user using(user_id) where meeting_room_user.meeting_room_id = mr.meeting_room_id and meeting_room_user.meeting_room_profile_id = 4';
        $this->filters['user_attendee'] = array('column'=>'meeting_room_user.user_id','type'=>'exists','select'=>$select);
        $this->filters['user_attendee_auth_mode'] = array('column'=>'user.auth_mode_id','type'=>'exists_key','select'=>$select);
        $this->filters['user_attendee_login'] = array('column'=>'user.login','type'=>'exists_text','select'=>$select);
        $this->filters['user_attendee_name'] = array('column'=>'user.name','type'=>'exists_text','select'=>$select);
        
        /*
        Filtros a utilizar:
        - Nome : texto
        - Hora de Início : data/hora
        - Hora de Fim : data/hora
        - Fuso Horário : combo
        - Tipo da Sala : combo ("Apenas convidados", "Apenas usuários autenticados", "Pública")
        - URL da sala : texto
        - Quantidade Máxima de Participantes : inteiro
        - Permissões - Administradores : (abre segundo campo)
        - Permissões - Moderadores : (abre segundo campo)
        - Permissões - Palestrantes : (abre segundo campo)
        - Permissões - Participantes : (abre segundo campo)
        - Observações : texto
        
        Nos quatro casos, o segundo campo abrirá com as seguintes opções:
        - Usuário (Lista)-> abre seleção dos usuários possíveis
        - Usuário (Login)-> Texto
        - Usuário (Nome) -> Texto
        - Usuário (Tipo) -> abre combo com "LDAP" e "local"
        - Grupo (Lista)  -> abre seleção dos grupos possíveis
        - Grupo (Nome)   -> Texto
        - Grupo (Tipo)   -> abre combo com "LDAP" e "local"
        */
        
        /* Old method, keep for a while */
        $this->filters['group_admin_local'] = array('column'=>'group_id','type'=>'exists','select'=>'select 1 from meeting_room_group where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 1 and auth_mode_id = 1');
        $this->filters['group_admin_ldap'] = array('column'=>'group_id','type'=>'exists','select'=>'select 1 from meeting_room_group where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 1 and auth_mode_id = 2');
        $this->filters['group_moderator_local'] = array('column'=>'group_id','type'=>'exists','select'=>'select 1 from meeting_room_group where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 2 and auth_mode_id = 1');
        $this->filters['group_moderator_ldap'] = array('column'=>'group_id','type'=>'exists','select'=>'select 1 from meeting_room_group where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 2 and auth_mode_id = 2');
        $this->filters['group_presenter_local'] = array('column'=>'group_id','type'=>'exists','select'=>'select 1 from meeting_room_group where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 3 and auth_mode_id = 1');
        $this->filters['group_presenter_ldap'] = array('column'=>'group_id','type'=>'exists','select'=>'select 1 from meeting_room_group where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 3 and auth_mode_id = 2');
        $this->filters['group_attendee_local'] = array('column'=>'group_id','type'=>'exists','select'=>'select 1 from meeting_room_group where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 4 and auth_mode_id = 1');
        $this->filters['group_attendee_ldap'] = array('column'=>'group_id','type'=>'exists','select'=>'select 1 from meeting_room_group where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 4 and auth_mode_id = 2');
        $this->filters['user_admin_local'] = array('column'=>'user_id','type'=>'exists','select'=>'select 1 from meeting_room_user where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 1 and auth_mode_id = 1');
        $this->filters['user_admin_ldap'] = array('column'=>'user_id','type'=>'exists','select'=>'select 1 from meeting_room_user where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 1 and auth_mode_id = 2');
        $this->filters['user_moderator_local'] = array('column'=>'user_id','type'=>'exists','select'=>'select 1 from meeting_room_user where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 2 and auth_mode_id = 1');
        $this->filters['user_moderator_ldap'] = array('column'=>'user_id','type'=>'exists','select'=>'select 1 from meeting_room_user where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 2 and auth_mode_id = 2');
        $this->filters['user_presenter_local'] = array('column'=>'user_id','type'=>'exists','select'=>'select 1 from meeting_room_user where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 3 and auth_mode_id = 1');
        $this->filters['user_presenter_ldap'] = array('column'=>'user_id','type'=>'exists','select'=>'select 1 from meeting_room_user where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 3 and auth_mode_id = 2');
        $this->filters['user_attendee_local'] = array('column'=>'user_id','type'=>'exists','select'=>'select 1 from meeting_room_user where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 4 and auth_mode_id = 1');
        $this->filters['user_attendee_ldap'] = array('column'=>'user_id','type'=>'exists','select'=>'select 1 from meeting_room_user where meeting_room_id = mr.meeting_room_id and meeting_room_profile_id = 4 and auth_mode_id = 2');
        
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
                $desc = $this->view->response['row']['name'].' ('.$this->view->response['row']['meeting_room_id'].')';
            }
            $this->view->response = null;
        }

        if(in_array($this->getRequest()->getActionName(),array('post','put'))) {
            $new = $this->_helper->params();
        }
        
        IMDT_Util_Log::write($desc,$new,$old);
    }
    
    public function rowHandler(&$row) {
	   if(! (isset($row['_editable']) && isset($row['_removable']))){
            $row['_editable'] = '1';
            $row['_removable'] = '1';

            if(IMDT_Util_Auth::getInstance()->get('globalWrite') == false){
                $row['_editable'] = '0';
                $row['_removable'] = '0';
            }
        }
    }
    
    public function deleteAction() {
        try {
            if(strpos($this->_id, ',') == -1) {
                $rowModel = $this->model->find($this->_id)->current();
                if($rowModel == null) throw new Exception(sprintf($this->_helper->translate('Meeting Room %s not found.'), $this->_id));
                
                //$name = $rowModel->name;
                $this->view->response = array('success'=>'1','msg'=>$this->_helper->translate('The meeting room was deleted successfully.'));
                $rowModel->delete();
            } else {
                //$this->model->delete('meeting_room_id in ('.$this->_id.')');
                $collection = $this->model->find(explode(',',$this->_id));
                $i = 0;
                foreach($collection as $row) {
                    $row->delete();
                    $i++;
                }
                
                $this->view->response = array('success'=>'1','msg'=>sprintf($this->_helper->translate('%s meeting rooms was deleted successfully.'),$i));
            }
            
        } catch(Exception $e) {
            $this->view->response = array('success'=>'0','msg'=>$e->getMessage());
        }
    }
    
    public function getAction() {
        try {
            $this->select->where('mr.meeting_room_id = ?',$this->_id);
            $rowModel = $this->model->fetchRow($this->select);
            
            if($rowModel == null) throw new Exception(sprintf($this->_helper->translate('Meeting Room %s not found.'), $this->_id));
            
            $rowModel = $rowModel->toArray();
            
            $this->rowHandler($rowModel);
            
            $arrResponse = array();
            $arrResponse['row'] = $rowModel;
            $arrResponse['success'] = '1';
            $arrResponse['msg'] = $this->_helper->translate('Meeting Room retrieved successfully.');
            
            $this->view->response = $arrResponse;
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
            
            //echo $this->select;die;
            
            //file_put_contents('log_sql.txt', $this->select);
            $collection = $this->model->fetchAll($this->select);
            $rCollection = ($collection != null ? $collection->toArray() : array());
            
            $userAcessProfileId = IMDT_Util_Auth::getInstance()->get('access_profile_id');
            
            $myRooms = $this->model->findMyRooms(null, $this->model->select());
            $rMyRooms = ($myRooms != null ? $myRooms->toArray() : array());

            if(count($rMyRooms) > 0){
                $rMyRooms = BBBManager_Util_MeetingRoom::detectUserProfileInMeeting($rMyRooms);
            }

            $rIdMyRooms = array();

            if(count($rMyRooms) > 0){
                foreach($rMyRooms as $myRoom){
                    if(in_array($myRoom['user_profile_in_meeting'], array(BBBManager_Config_Defines::$ROOM_ADMINISTRATOR_PROFILE))){
                        $rIdMyRooms[] = $myRoom['meeting_room_id'];    
                    }
                }
            }
            
            $hasSupportProfile = (BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE == $userAcessProfileId);
            $hasPrivilegedUserProfile = (BBBManager_Config_Defines::$SYSTEM_PRIVILEGED_USER_PROFILE == $userAcessProfileId);
            $hasAdministratorProfile = (BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE == $userAcessProfileId);
            
            /*ob_start();
            echo '$hasSupportProfile - ' . ($hasSupportProfile ? 'OK' : 'NOK') . '    ';
            echo '$hasPrivilegedUserProfile - ' . ($hasPrivilegedUserProfile ? 'OK' : 'NOK') . '    ';
            echo '$hasAdministratorProfile - ' . ($hasAdministratorProfile ? 'OK' : 'NOK') . '    ';
            $conteudo = ob_get_clean();
            throw new Exception($conteudo);*/
            
            $finalRoomsCollection = array();
            
            if($hasAdministratorProfile){
                $finalRoomsCollection = $rCollection;
            }elseif($hasSupportProfile && (! $hasPrivilegedUserProfile)){
                $finalRoomsCollection = $rCollection;
            }elseif($hasSupportProfile && $hasPrivilegedUserProfile){
                $finalRoomsCollection = $rCollection;
                
                foreach($finalRoomsCollection as &$room){
                    if(array_search($room['meeting_room_id'], $rIdMyRooms) !== false){
                        $room['_editable'] = '1';
                        $room['_removable'] = '1';
                    }else{
                        $room['_editable'] = '0';
                        $room['_removable'] = '0';

//                        throw new Exception($room['meeting_room_id'] .' not my room');
                    }
                }
            }elseif((!$hasSupportProfile) && $hasPrivilegedUserProfile){
                foreach($rCollection as &$room){
                    if(array_search($room['meeting_room_id'], $rIdMyRooms) !== false){
                        $room['_editable'] = '1';
                        $room['_removable'] = '1';
                        
                        $finalRoomsCollection[] = $room;
                    }
                }
            }
            
            $rCollection = $finalRoomsCollection;
            
            array_walk($rCollection, array($this, 'rowHandler'));
            
            $this->view->response = array('success'=>'1','collection'=>$rCollection,'msg'=>sprintf($this->_helper->translate('%s meeting rooms retrieved successfully.'),count($rCollection)));
        } catch(Exception $e) {
            $this->view->response = array('success'=>'0','msg'=>$e->getMessage());
        }
    }
    
    
    public function parseValidators($data) {
        $arrErrorMessages = array();
        
        foreach($this->columnValidators as $column=>$validators) {
            foreach($validators as $currValidator) {
                $value = (isset($data[$column]) ? $data[$column] : '');
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
    
    public function doConversions(&$row) {
        if(isset($row->date_start)) {
            $row->date_start = IMDT_Util_Date::filterDatetimeToApi($row->date_start);
        }
        
        if(isset($row->date_end)) {
            $row->date_end = IMDT_Util_Date::filterDatetimeToApi($row->date_end);
        }
    }
    
    public function postAction() {
        unset($this->columnValidators['main_date_start']);
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
            
            $this->doConversions($rowModel);
            $newRowId = $rowModel->save();
            
            $groups['group_admin_local'] = array(1,1);
            $groups['group_admin_ldap'] = array(1,2);
            $groups['group_moderator_local'] = array(2,1);
            $groups['group_moderator_ldap'] = array(2,2);
            $groups['group_presenter_local'] = array(3,1);
            $groups['group_presenter_ldap'] = array(3,2);
            $groups['group_attendee_local'] = array(4,1);
            $groups['group_attendee_ldap'] = array(4,2);
            
            foreach($groups as $name=>$attr) {
                if(!isset($data[$name]) || strlen((string) $data[$name]) == 0) continue;
                
                list($meeting_room_profile_id,$auth_mode_id) = $attr;
                $ids = (string) $data[$name];
                $sqlInsert = 'insert into meeting_room_group(meeting_room_id,meeting_room_profile_id,auth_mode_id,group_id) 
                                select '.$newRowId.', '.$meeting_room_profile_id.',  g.auth_mode_id, g.group_id
                                from `group` g where g.auth_mode_id = '.$auth_mode_id.' and g.group_id in ('.$ids.')';
                                
                $this->model->getAdapter()->query($sqlInsert);
            }
            
            $users['user_admin_local'] = array(1,1);
            $users['user_admin_ldap'] = array(1,2);
            $users['user_moderator_local'] = array(2,1);
            $users['user_moderator_ldap'] = array(2,2);
            $users['user_presenter_local'] = array(3,1);
            $users['user_presenter_ldap'] = array(3,2);
            $users['user_attendee_local'] = array(4,1);
            $users['user_attendee_ldap'] = array(4,2);
            
            foreach($users as $name=>$attr) {
                if(!isset($data[$name]) || strlen((string) $data[$name]) == 0) continue;
                
                list($meeting_room_profile_id,$auth_mode_id) = $attr;
                $ids = (string) $data[$name];
                
                $this->model->getAdapter()->query('insert into meeting_room_user(meeting_room_id,meeting_room_profile_id,user_id, auth_mode_id) 
                                                    select '.$newRowId.', '.$meeting_room_profile_id.',  u.user_id, u.auth_mode_id
                                                    from user u where u.auth_mode_id = '.$auth_mode_id.' and u.user_id in ('.$ids.')');
            }
            
            $this->model->getAdapter()->commit();
            $this->view->response = array('success'=>'1','msg'=>'','id'=>$newRowId,'msg'=>$this->_helper->translate('Meeting room has been created successfully.'));
        } catch(Exception $e) {
            $this->view->response = array('success'=>'0','msg'=>$e->getMessage());
        }
    }

    public function putAction() {
        unset($this->columnValidators['main_date_start']);
        $this->model->getAdapter()->beginTransaction();
        
        try {
            if($this->_id == null) $this->forward('post');

            $data = $this->_helper->params();
            if(empty($data)) throw new Exception($this->_helper->translate('Invalid Request.'));
            
            
            if(!isset($data['date_start'])) unset($this->columnValidators['date_start']);
            if(!isset($data['date_end'])) unset($this->columnValidators['date_end']);
            if(!isset($data['name'])) unset($this->columnValidators['name']);
            if(!isset($data['participants_limit'])) unset($this->columnValidators['participants_limit']);
            if(!isset($data['url'])) unset($this->columnValidators['url']);
            
            $arrErrorMessages = $this->parseValidators($data);
            if(count($arrErrorMessages) > 0) {
                $this->view->response = array('success'=>'0','msg'=>$arrErrorMessages);
                return;
            }
            
            $rowModel = $this->model->find($this->_id)->current();
            if($rowModel == null) throw new Exception($this->_helper->translate('Meeting Room %s not found.'));
            
            $select = $this->model->select();
            $select->where('mr.meeting_room_id = ?', $this->_id);
            
            $myRoomsCollection = $this->model->findMyRooms(false, $select);
            
            $rRoomProfile = BBBManager_Util_MeetingRoom::detectUserProfileInMeeting($myRoomsCollection->toArray());
            
            if(($rRoomProfile == null) && (IMDT_Util_Auth::getInstance()->get('user_access_profile') != BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE)){
                throw new Exception($this->_helper->translate('Error fetching user profile in meeting room.'));
            }
            $rRoomProfile = current($rRoomProfile);
            
            if(IMDT_Util_Auth::getInstance()->get('user_access_profile') != BBBManager_Config_Defines::$SYSTEM_ADMINISTRATOR_PROFILE){
                if(in_array($rRoomProfile['user_profile_in_meeting'], array(BBBManager_Config_Defines::$ROOM_ADMINISTRATOR_PROFILE, BBBManager_Config_Defines::$ROOM_MODERATOR_PROFILE)) === false){
                    throw new Exception($this->_helper->translate('You don\'t have access to the requested resource'));
                }
            }
            
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
            
            $this->doConversions($rowModel);
            $rowModel->save();
            
            $groups['group_admin_local'] = array(1,1);
            $groups['group_admin_ldap'] = array(1,2);
            $groups['group_moderator_local'] = array(2,1);
            $groups['group_moderator_ldap'] = array(2,2);
            $groups['group_presenter_local'] = array(3,1);
            $groups['group_presenter_ldap'] = array(3,2);
            $groups['group_attendee_local'] = array(4,1);
            $groups['group_attendee_ldap'] = array(4,2);
            
            foreach($groups as $name=>$attr) {
                if(!isset($data[$name])) continue;
                
                list($meeting_room_profile_id,$auth_mode_id) = $attr;
                if(strlen((string) $data[$name]) == 0) {
                    $this->model->getAdapter()->query('delete from meeting_room_group where meeting_room_id = '.$this->_id.' and meeting_room_profile_id = '.$meeting_room_profile_id.' and auth_mode_id = '.$auth_mode_id);
                } else {
                    $ids = (string) $data[$name];
                    $this->model->getAdapter()->query('delete from meeting_room_group where meeting_room_id = '.$this->_id.' and meeting_room_profile_id = '.$meeting_room_profile_id.' and auth_mode_id = '.$auth_mode_id.' and group_id not in ('.$ids.')');
                    $sqlInsert = 'insert into meeting_room_group(meeting_room_id,meeting_room_profile_id,auth_mode_id,group_id) 
                                    select '.$this->_id.', '.$meeting_room_profile_id.',  g.auth_mode_id, g.group_id
                                    from `group` g where g.auth_mode_id = '.$auth_mode_id.' and g.group_id in ('.$ids.') and not exists
                                                                (select 1 from meeting_room_group mrg 
                                                                where mrg.meeting_room_id = '.$this->_id.' 
                                                                and mrg.meeting_room_profile_id = '.$meeting_room_profile_id.' 
                                                                and mrg.auth_mode_id = g.auth_mode_id 
                                                                and mrg.group_id = g.group_id)';
                    $this->model->getAdapter()->query($sqlInsert);
                }
            }
            
            $users['user_admin_local'] = array(1,1);
            $users['user_admin_ldap'] = array(1,2);
            $users['user_moderator_local'] = array(2,1);
            $users['user_moderator_ldap'] = array(2,2);
            $users['user_presenter_local'] = array(3,1);
            $users['user_presenter_ldap'] = array(3,2);
            $users['user_attendee_local'] = array(4,1);
            $users['user_attendee_ldap'] = array(4,2);
            
            foreach($users as $name=>$attr) {
                if(!isset($data[$name])) continue;
                
                list($meeting_room_profile_id,$auth_mode_id) = $attr;
                if(strlen((string) $data[$name]) == 0) {
                    $this->model->getAdapter()->query('delete from meeting_room_user where meeting_room_id = '.$this->_id.' and meeting_room_profile_id = '.$meeting_room_profile_id.' and auth_mode_id = '.$auth_mode_id);
                } else {
                    $ids = (string) $data[$name];
                    
                    $this->model->getAdapter()->query('delete from meeting_room_user where meeting_room_id = '.$this->_id.' and meeting_room_profile_id = '.$meeting_room_profile_id.' and auth_mode_id = '.$auth_mode_id.' and user_id not in ('.$ids.')');
                    $this->model->getAdapter()->query('insert into meeting_room_user(meeting_room_id,meeting_room_profile_id,user_id, auth_mode_id) 
                                                        select '.$this->_id.', '.$meeting_room_profile_id.',  u.user_id, u.auth_mode_id
                                                        from user u where u.auth_mode_id = '.$auth_mode_id.' and u.user_id in ('.$ids.')
                                                        and not exists(select 1 from meeting_room_user mru 
                                                                                    where mru.meeting_room_id = '.$this->_id.' 
                                                                                    and mru.meeting_room_profile_id = '.$meeting_room_profile_id.' 
                                                                                    and mru.auth_mode_id = '.$auth_mode_id.' 
                                                                                    and mru.user_id = u.user_id)');
                }
            }
            
            $this->model->getAdapter()->commit();
            $this->view->response = array('success'=>'1','msg'=>$this->_helper->translate('Meeting room has been successfully changed.'));
        } catch(Exception $e) {
            $this->model->getAdapter()->rollBack();
            $this->view->response = array('success'=>'0','msg'=>$e->getMessage());
        }
    }
    
    public function importAction(){
	$hasTransaction = false;
	try{
	    $inputData = $this->_helper->params();
	    $csvFileContents = (isset($inputData['file-contents']) ? $inputData['file-contents'] : null);
	    
	    if($csvFileContents == null){
		throw new Exception($this->_helper->translate('Invalid CSV file content'));
	    }
	    
	    $this->model->getAdapter()->beginTransaction();
	    $hasTransaction = true;
	    $records = $this->model->importCsv($csvFileContents);
	    $this->model->getAdapter()->commit();
	    
	    $this->view->response = array('success' => '1', 'msg' => sprintf($this->_helper->translate('%s records imported'), $records) . '.');
	}catch(Exception $e){
	    if($hasTransaction == true){
		$this->model->getAdapter()->rollback();
	    }
	    $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
	}
    }
}