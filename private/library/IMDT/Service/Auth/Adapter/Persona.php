<?php

class IMDT_Service_Auth_Adapter_Persona {

    /**
     * Scheme, hostname and port
     */
    protected $audience;

    /**
     * Constructs a new Persona (optionally specifying the audience)
     */
    public function __construct($audience = NULL) {
	$this->audience = $audience ? : $this->guessAudience();
    }

    public function authenticate($assertion, $username = '') {
	$personaResponse = $this->verifyAssertion($assertion);

	if ($personaResponse->status == 'okay') {

	    $userModel = new BBBManager_Model_User();
	    $dbRecord = $userModel->findByEmail($personaResponse->email);

	    if ($dbRecord == null) {
		$authResult = new IMDT_Service_Auth_Result(new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $personaResponse->email));

		if ($username == '') {
		    $authResult->setNeedExtraInformation(true);
		    IMDT_Service_Auth::getInstance()->setAuthResult($authResult);
		    return true;
		}

		try {
		    $userId = $userModel->insert(array(
			'email' => $personaResponse->email,
			'name' => $username,
			'login' => $personaResponse->email,
			'auth_mode_id' => BBBManager_Config_Defines::$PERSONA_AUTH_MODE,
			'access_profile_id' => BBBManager_Config_Defines::$SYSTEM_USER_PROFILE
			    ));

		    $dbRecord = $userModel->find($userId)->current()->toArray();
		} catch (Exception $e) {
		    throw new Exception($e->getMessage());
		}
	    }

	    if ($dbRecord['auth_mode_id'] != BBBManager_Config_Defines::$PERSONA_AUTH_MODE) {
		throw new Exception(IMDT_Util_Translate::_('This email is already in use by an internal user of the system, please try to use another email.'));
	    }

	    $authResult = new IMDT_Service_Auth_Result(new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $personaResponse->email));
	    $authSettings = IMDT_Service_Auth::getInstance()->getSettings();
	    $userInfo = array();

	    if (isset($authSettings['database']['key_mapping']) && (is_array($authSettings['database']['key_mapping']))) {
		foreach ($authSettings['database']['key_mapping'] as $key => $value) {
		    if (isset($dbRecord[$value])) {
			$userInfo[$key] = $dbRecord[$value];
		    }
		}
		$authResult->setAuthData($userInfo);
	    }

	    IMDT_Service_Auth::getInstance()->setAuthResult($authResult);

	    IMDT_Service_Auth::getInstance()->afterSuccessfulAuthentication();

	    return true;
	} else {
	    return false;
	}
    }

    public function verifyAssertion($assertion) {
	$postdata = 'assertion=' . urlencode($assertion) . '&audience=' . urlencode($this->audience);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://verifier.login.persona.org/verify");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	$response = curl_exec($ch);

	if (curl_errno($ch) != 0) {
	    throw new Exception(IMDT_Util_Translate::_('Persona Authentication failed') . ' - ' . curl_error($ch));
	}

	curl_close($ch);

	return json_decode($response);
    }

    /**
     * Guesses the audience from the web server configuration
     */
    protected function guessAudience() {
	$audience = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
	$audience .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
	return $audience;
    }

}