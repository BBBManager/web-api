<?php
ini_set('display_errors', 'on');
ini_set('error_reporting', E_ALL);
ini_set('date.timezone', 'America/Sao_Paulo');
set_time_limit(0);

try {
    defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../private/application'));	
    defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));
    set_include_path(implode(PATH_SEPARATOR, array(realpath(APPLICATION_PATH . '/../library'))));
    require_once 'Zend/Application.php';
    $application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
    $application->bootstrap();
    
    $adapter = Zend_Db::factory(
        'PDO_MYSQL', 
        array(
            'host'      => '10.30.10.10',
            'port'      => '33306',
            'dbname'    => 'bbb',
            'username'  => 'bbb',
            'password'  => 'bbb'
        )
    );
    Zend_Db_Table_Abstract::setDefaultAdapter($adapter);
    
    // Invalidate the auth.ini cache
    BBBManager_Cache_Auth::getInstance()->clean();
    
    $ldapSettings = IMDT_Service_Auth::getInstance()->getSettings();
    /*
    //$ldapFilter = '(&(!(userAccountControl:1.2.840.113556.1.4.803:=2))(!(samaccountname=$*))(memberOf=CN=webconf_user,OU=BBB,OU=Sistemas,dc=poapgj,dc=mp,dc=rs,dc=gov,dc=br))';
    $ldapFilter = $ldapSettings['ldap']['users_sync_query'];*/
    $ldapMembersList = array();
    IMDT_Util_Ldap::getInstance()->findMembersRecursively($ldapMembersList);
    
    $userModel = new BBBManager_Model_User();
    $findLdapUsers = $userModel->select();
    $findLdapUsers->where('auth_mode_id = ?', BBBManager_Config_Defines::$LDAP_AUTH_MODE);
    
    $findLdapUsers->where('1 = ?', new Zend_Db_Expr($userModel->getSqlStatementForActiveUsers()));
    
    $dbLdapUsers = $userModel->fetchAll($findLdapUsers);
    $dbLdapUsers = (($dbLdapUsers instanceof Zend_Db_Table_Rowset) ? $dbLdapUsers->toArray() : array());
    $rDbLdapUsers = array();
    
    if(count($dbLdapUsers) > 0){
        foreach($dbLdapUsers as $dbLdapUser){
            //$rDbLdapUsers[strtolower(IMDT_Util_Ldap::ldapNameToCleanName($dbLdapUser['ldap_cn']))] = $dbLdapUser;
            $rDbLdapUsers[strtolower($dbLdapUser['login'])] = $dbLdapUser;
        }
    }
    
    if(count($ldapMembersList) > 0){
        $rLdapMembersList = array();
        foreach($ldapMembersList as $ldapMember => $ldapMemberInfo){
            //$rLdapMembersList[strtolower(IMDT_Util_Ldap::ldapNameToCleanName($ldapMember))] = $ldapMemberInfo;
            $rLdapMembersList[strtolower(current($ldapMemberInfo['samaccountname']))] = $ldapMemberInfo;
        }
        
        $rDeletes = array_diff_key($rDbLdapUsers, $rLdapMembersList);
        $rInserts = array_diff_key($rLdapMembersList, $rDbLdapUsers);
        $rUpdates = array_intersect_key($rDbLdapUsers, $rLdapMembersList);
        $rMustUpdate = array();
        
        $emailMapping = $ldapSettings['ldap']['key_mapping']['email'];
        $fullNameMapping = $ldapSettings['ldap']['key_mapping']['full_name'];
        $loginMapping = $ldapSettings['ldap']['key_mapping']['login'];

        foreach($rUpdates as $ldapMember => $ldapMemberInfo){
            $dataUpdate = array();
            
            $dbFullName = $ldapMemberInfo['name'];
            $dbLogin = $ldapMemberInfo['login'];
            $dbEmail = $ldapMemberInfo['email'];
            $dbDn = $ldapMemberInfo['ldap_cn'];
            
            //$ldapFullName = ucwords(strtolower(IMDT_Util_String::replaceTags($fullNameMapping, array('displayname'=>current($rLdapMembersList[$ldapMember]['displayname'])), true)));
            $ldapFullName = IMDT_Util_String::camelize(IMDT_Util_String::replaceTags($fullNameMapping, array('displayname'=>current($rLdapMembersList[$ldapMember]['displayname'])), true));
            $ldapLogin = strtolower(IMDT_Util_String::replaceTags($loginMapping, array('samaccountname'=>current($rLdapMembersList[$ldapMember]['samaccountname']))));
            $ldapEmail = (isset($rLdapMembersList[$ldapMember]['email']) ? current($rLdapMembersList[$ldapMember]['email']) : IMDT_Util_String::replaceTags($emailMapping, array('samaccountname'=>$ldapLogin), true));
            $ldapDn = $rLdapMembersList[$ldapMember]['dn'];
            
            if($dbFullName != $ldapFullName){
                $dataUpdate['name'] = $ldapFullName;
            }
                
            if($dbEmail != $ldapEmail){
                $dataUpdate['email'] = $ldapEmail;
            }
                
            if($dbLogin != $ldapLogin){
                $dataUpdate['login'] = $ldapLogin;
            }
            
            if($ldapDn != $dbDn){
                $dataUpdate['ldap_cn'] = IMDT_Util_Ldap::ldapNameToCleanName($ldapDn);
            }
            
            if(count($dataUpdate) > 0){
                $rMustUpdate[$ldapMemberInfo['user_id']] = $dataUpdate;
            }
        }
        
        /*echo '<h1>Updates</h1>';
        echo '<pre>';
        var_dump($rMustUpdate);
        echo '</pre>';
        echo '<hr/>';
        echo '<h1>Inserts</h1>';
        echo '<pre>';
        var_dump($rInserts);
        echo '</pre>';
        echo '<hr/>';
        echo '<h1>Deletes</h1>';
        echo '<pre>';
        var_dump($rDeletes);
        echo '</pre>';die;*/
        
        $adapter = $userModel->getAdapter();
        $adapter->beginTransaction();

        try{
            foreach($rDeletes as $userToDelete){
                $whereInactivate = $adapter->quoteInto('user_id = ?', $userToDelete['user_id']);
                $userModel->update(array('valid_to' => date('Y-m-d', strtotime('now - 1 day'))), $whereInactivate);
            }

            foreach($rMustUpdate as $userId => $updateData){
                $whereUpdate = $adapter->quoteInto('user_id = ?', $userId);
                $userModel->update($updateData, $whereUpdate);
            }

            foreach($rInserts as $userToInsert){

                $ldapLogin = strtolower(IMDT_Util_String::replaceTags($loginMapping, array('samaccountname'=>current($userToInsert['samaccountname']))));

                $rInsertData = array(
                    'auth_mode_id'      => BBBManager_Config_Defines::$LDAP_AUTH_MODE,
                    'login'             => $ldapLogin,
                    'name'              => IMDT_Util_String::camelize(IMDT_Util_String::replaceTags($fullNameMapping, array('displayname'=>current($userToInsert['displayname'])), true)),
                    'email'             => (isset($userToInsert['email']) ? current($userToInsert['email']) : IMDT_Util_String::replaceTags($emailMapping, array('samaccountname'=>$ldapLogin), true)),
                    'ldap_cn'           => IMDT_Util_Ldap::ldapNameToCleanName($userToInsert['dn']),
                    'access_profile_id' => BBBManager_Config_Defines::$SYSTEM_USER_PROFILE
                );

                $userModel->insert($rInsertData);
            }
        } catch (Exception $ex) {
            $adapter->rollBack();
        }
    }
    $adapter->commit();
    
    echo "\n";
    echo "\n";
    echo 'LDAP sync';
    echo "\n";
    echo '---------';
    echo "\n";
    echo count($rDeletes) . ' users inactivated';
    echo "\n";
    echo count($rMustUpdate) . ' users updated';
    echo "\n";
    echo count($rInserts) . ' users inserted';
    
    /*echo '<pre>';
    var_dump($ldapMembersList);
    echo '</pre>';die;*/
} catch (Exception $e) {
    if(function_exists('http_response_code')){
        http_response_code(500);    
    }
    echo "<pre>ERRO: ".$e->getMessage();
    die;
}