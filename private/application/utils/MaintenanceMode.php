<?php

class BBBManager_Util_MaintenanceMode {

    public static function generateAuthorizationHash($maintenanceDescription) {
        return IMDT_Util_Hash::generate($maintenanceDescription);
    }

    public static function notifyAdministrators($maintenanceAuthorizationHash) {
        try {
            $view = new Zend_View();
            $view->setScriptPath(APPLICATION_PATH);

            $view->title = $view->translate('Maintenance mode activated');

            $emailPrepend = $view->render('views/scripts/layout/email-start.phtml');
            $emailAppend = $view->render('views/scripts/layout/email-end.phtml');

            $greeting = $view->translate('Hello Administrator');

            $message = $view->translate('This is an automatic message, informing you that the maintenance mode was activated, use the link below to access the system during the maintenance period') . '.';
            $message .= '</p><p>';
            $message .= sprintf('<a href="%s?mah=%s">%s?mah=%s</a>', IMDT_Util_Config::getInstance()->get('web_base_url'), $maintenanceAuthorizationHash, IMDT_Util_Config::getInstance()->get('web_base_url'), $maintenanceAuthorizationHash);
            $message .= '</p>';

            $emailBody = $view->partial('views/emails/maintenance.phtml', array('greeting' => $greeting, 'message' => $message));

            $defaultSubjectName = '';

            if (BBBManager_Util_Skinning::getInstance()->get('company_name') != null) {
                $defaultSubjectName = BBBManager_Util_Skinning::getInstance()->get('company_name');
            }

            if (BBBManager_Util_Skinning::getInstance()->get('system_title') != null) {
                $defaultSubjectName .= ' ' . BBBManager_Util_Skinning::getInstance()->get('system_title');
            }

            $usersModel = new BBBManager_Model_User();
            $administrators = $usersModel->findAdministrators();
            $administrators = ($administrators instanceof Zend_Db_Table_Rowset ? $administrators->toArray() : $administrators);

            if (count($administrators) > 0) {
                $mail = new Zend_Mail('utf-8');
                $mail->setSubject($defaultSubjectName . ' - ' . $view->translate('Maintenance mode activated'))
                        ->setBodyHtml($emailPrepend . $emailBody . $emailAppend);

                foreach ($administrators as $administrator) {
                    $mail->addTo($administrator['email']);
                }

                $mail->send();
            }

            return true;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

}
