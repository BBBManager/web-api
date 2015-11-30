<?php
class IMDT_Service_Auth_Result{
    
    private $_isValid;
    private $_authData;
    private $_tryNextAuthMode;
    private $_needExtraInformation;

    public function __construct($abstractResult, $tryNextAuthMode = false){
        if($abstractResult instanceof Zend_Auth_Result){
            $this->_isValid = $abstractResult->isValid();
        }
        
        $this->_tryNextAuthMode = $tryNextAuthMode;
    }
    
    public function getAuthData() {
        return $this->_authData;
    }

    public function setAuthData($authData) {
        $this->_authData = $authData;
    }
    
    public function isValid(){
        return $this->_isValid;
    }
    
    public function setIsValid($isValid){
        $this->_isValid = $isValid;
    }
    
    public function getTryNextAuthMode() {
        return $this->_tryNextAuthMode;
    }

    public function setTryNextAuthMode($tryNextAuthMode) {
        $this->_tryNextAuthMode = $tryNextAuthMode;
    }
    
    public function getClientData(){
	$clientData = array();
	
	if(is_array($this->_authData)){
            $userAccessProfile = $this->_authData['access_profile_id'];
            $clientData['user_access_profile'] = $userAccessProfile;
            
            $onlyUserAccessProfile = ($userAccessProfile == BBBManager_Config_Defines::$SYSTEM_USER_PROFILE);
            $onlySupportAccessProfile = ($userAccessProfile == BBBManager_Config_Defines::$SYSTEM_SUPPORT_PROFILE);
            
            $newUserPrefix = IMDT_Util_Config::getInstance()->get('new_user_prefix');
            if(!empty($newUserPrefix)) {
                $clientData['new_user_prefix'] = $newUserPrefix;
            }
	    
	    $globalRead = true;
	    $globalWrite = true;
	    
	    if($onlyUserAccessProfile == true){
		$clientData['systemUser'] = true;
		$globalRead = false;
		$globalWrite = false;

	    }
	    
	    if($onlySupportAccessProfile == true){
		$globalRead = true;
		$globalWrite = false;
	    }
	    
	    $this->_authData['globalRead'] = $globalRead;
	    $this->_authData['globalWrite'] = $globalWrite;
	    
	    $authDataNs = new Zend_Session_Namespace('authData');
	    $authDataNs->authData['globalRead'] = $globalRead;
	    $authDataNs->authData['globalWrite'] = $globalWrite;
	    
	    foreach($this->_authData as $key => $value){
		if(in_array($key, array('id','email','full_name','auth_mode','login','token', 'globalRead', 'globalWrite'))){
		    $clientData[$key] = $value;
		}
	    }
	}
	
	$clientData['loggedInAt'] = date('Y-m-d H:i:s');
	
	return $clientData;
    }
    
    public function getNeedExtraInformation() {
	return $this->_needExtraInformation;
    }

    public function setNeedExtraInformation($needExtraInformation) {
	$this->_needExtraInformation = $needExtraInformation;
    }
}