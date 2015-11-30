<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    private $_view;

    protected function _initDateCache() {
        $frontendOptions = array(
            'lifetime' => 600, // 10 minutes
            'automatic_serialization' => true
        );

        $cacheDir = APPLICATION_PATH . '/../tmp/cache';
        IMDT_Util_File::checkPath($cacheDir, true);

        $backendOptions = array('cache_dir' => $cacheDir);

        $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
        Zend_Date::setOptions(array('cache' => $cache));
    }

    protected function _initSession() {

        $this->bootstrap('frontController');
        $front = $this->getResource('frontController');
        $front->setRequest(new Zend_Controller_Request_Http());

        $request = $front->getRequest();

        $sid = $request->getHeader('token', '');

        $sessionSavePath = APPLICATION_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'session';
        IMDT_Util_File::checkPath($sessionSavePath, true);
        $opcoes = array(
            'save_path' => $sessionSavePath,
            'strict' => 'on'
        );
        Zend_Session::setOptions($opcoes);
        //$sid = isset($_POST['PHPSESSID']) ? $_POST['PHPSESSID'] : '';
        if (!empty($sid)) {
            Zend_Session::setId($sid);
        }
        Zend_Session::start();
    }

    protected function _initRestRouter() {
        /* $this->bootstrap('frontController');
          $frontController = Zend_Controller_Front::getInstance();

          $restRoute = new Zend_Rest_Route($frontController, array(), array('api'));
          $frontController->getRouter()->addRoute('api', $restRoute); */

        $frontController = Zend_Controller_Front::getInstance();

        // set custom request object
        // add the REST route for the API module only
        $restRoute = new Zend_Rest_Route($frontController, array(), array('api'));
        $frontController->getRouter()->addRoute('rest', $restRoute);

        $frontController->getRouter()->addRoute('reset-password', new IMDT_Rest_Controller_Route($frontController, '/api/users/:user_id/reset-password', array('controller' => 'users-reset-password')));
    }

    protected function _initActionHelpers() {
        Zend_Controller_Action_HelperBroker::addHelper(new IMDT_Controller_Helper_Params());
        Zend_Controller_Action_HelperBroker::addHelper(new IMDT_Controller_Helper_RestContexts());
        Zend_Controller_Action_HelperBroker::addHelper(new IMDT_Controller_Helper_ContextSwitch());
        Zend_Controller_Action_HelperBroker::addHelper(new IMDT_Controller_Helper_Translate());
    }

    /*
      protected function _initActionHelpers(){
      $contextSwitch = new REST_Controller_Action_Helper_ContextSwitch();
      Zend_Controller_Action_HelperBroker::addHelper($contextSwitch);

      $restContexts = new REST_Controller_Action_Helper_RestContexts();
      Zend_Controller_Action_HelperBroker::addHelper($restContexts);
      } */

    protected function _initErrorHandler() {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(
                array(
            'module' => 'api',
            'controller' => 'error',
            'action' => 'error'
                )
        ));
    }

    protected function _initViewHelpers() {
        $this->bootstrap('view');
        $this->_view = $this->getResource('view');

        $this->_view->addHelperPath('EasyBib/View/Helper', 'EasyBib_View_Helper');
    }

    protected function _initResourceLoader() {
        $this->_resourceLoader->addResourceType('cache', 'cache/', 'Cache');
        $this->_resourceLoader->addResourceType('config', 'configs/', 'Config');
        $this->_resourceLoader->addResourceType('util', 'utils/', 'Util');
    }

    protected function _initViewHeadDoctype() {
        $this->_view->doctype('HTML5');
    }

    protected function _initViewHeadTitle() {

        $defaultTitle = 'Missing or invalid skinning.ini file';

        if (BBBManager_Util_Skinning::getInstance()->get('system_title') != null) {
            $defaultTitle = BBBManager_Util_Skinning::getInstance()->get('system_title');
        }

        if (BBBManager_Util_Skinning::getInstance()->get('company_name') != null) {
            $defaultTitle .= ' ' . BBBManager_Util_Skinning::getInstance()->get('company_name');
        }

        $this->_view->headTitle()->exchangeArray(array());
        $this->_view->headTitle($defaultTitle)
                ->setSeparator(' | ');
    }

    protected function _initViewHeadMeta() {
        $this->_view->headMeta()->exchangeArray(array());
        $this->_view->headMeta()->setCharset('UTF-8')
                ->setHttpEquiv('Content-Type', 'text/html; charset=UTF-8')
                ->setHttpEquiv('Content-Language', 'pt-BR')
                ->setHttpEquiv('X-UA-Compatible', 'IE=edge,chrome=1')
                ->setName('keywords', '')
                ->setName('description', '')
                ->setName('author', 'iMDT - Negócios Inteligentes <contato@imdt.com.br>')
                ->setName('viewport', 'width=device-width')
                ->setName('og:title', '')
                ->setName('og:type', 'website')
                ->setName('og:site_name', 'IMDb')
                ->setName('og:description', '');
    }

    protected function _initJquery() {
        $this->_view->addHelperPath("ZendX/JQuery/View/Helper", "ZendX_JQuery_View_Helper");

        $this->_view->jQuery()->setLocalPath($this->_view->baseUrl('/resources/js/jquery/jquery-1.8.2.min.js'))
                ->setUiLocalPath($this->_view->baseUrl('/resources/js/jquery/ui/jquery.ui.custom.min-1.9.1.js'));

        ZendX_JQuery::enableView($this->_view);

        $this->_view->jQuery()->enable();
        $this->_view->jQuery()->uiEnable();
    }

    protected function _initCss() {
        $this->_view->headLink()->exchangeArray(array());
        $this->_view->headLink()
                ->appendStylesheet($this->_view->baseUrl('/resources/css/bootstrap/bootstrap.min.css'))
                ->appendStylesheet($this->_view->baseUrl('/resources/css/bootstrap/bootstrap-responsive.min.css'))
                ->appendStylesheet($this->_view->baseUrl('/resources/css/bootstrap/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css'))
                ->appendStylesheet($this->_view->baseUrl('/resources/js/jquery/plugins/select2/select2.css'))
                ->appendStylesheet($this->_view->baseUrl('/resources/js/jquery/plugins/datatables/css/jquery.dataTables.css'))
                ->appendStylesheet($this->_view->baseUrl('/resources/js/jquery/plugins/treetable/css/jquery.treeTable.2.3.0.css'))
                ->appendStylesheet($this->_view->baseUrl('/resources/css/imdt-select2-bootstrap.css'))
                ->appendStylesheet($this->_view->baseUrl('/resources/css/style.css'));
    }

    protected function _initJqueryPlugins() {
        $this->_view->jQuery()
                ->addJavascriptFile($this->_view->baseUrl('/resources/js/bootstrap/bootstrap.min.js'))
                ->addJavascriptFile($this->_view->baseUrl('/resources/js/bootstrap/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js'))
                ->addJavascriptFile($this->_view->baseUrl('/resources/js/jquery/plugins/select2/select2.min.js'))
                ->addJavascriptFile($this->_view->baseUrl('/resources/js/jquery/plugins/select2/select2_locale_pt-BR.js'))
                ->addJavascriptFile($this->_view->baseUrl('/resources/js/ckeditor/ckeditor.js'))
                ->addJavascriptFile($this->_view->baseUrl('/resources/js/jquery/plugins/datatables/jquery.dataTables.min.js'))
                ->addJavascriptFile($this->_view->baseUrl('/resources/js/jquery/plugins/treetable/jquery.treeTable.2.3.0.js'))
                ->addJavascriptFile($this->_view->baseUrl('/resources/js/xml2json/xml2json.js'))
                ->addJavascriptFile($this->_view->baseUrl('/resources/js/app.js'))
                ->addJavascriptFile($this->_view->baseUrl('/resources/js/main.js'));
        /* ->addStylesheet($this->_view->baseUrl('/resources/js/jquery/shadowbox/jquery.shadowbox-3.0.3.css')); */
    }

    protected function _initJavascript() {
        /* $this->_view->headScript()->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap-alert.js'))
          ->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap-button.js'))
          ->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap-carousel.js'))
          ->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap-collapse.js'))
          ->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap-dropdown.js'))
          ->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap-modal.js'))
          ->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap-popover.js'))
          ->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap-scrollspy.js'))
          ->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap-tab.js'))
          ->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap-tooltip.js'))
          ->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap-transition.js'))
          ->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap-typeahead.js'))
          ->appendFile($this->view->baseUrl('/resources/js/bootstrap/bootstrap.min.js')); */
    }

    protected function _initDefaultLayout() {
        $layout = Zend_Layout::startMvc();

        $layout->setLayoutPath(APPLICATION_PATH . '/views/scripts/layout')->setLayout('main');

        return $layout;
    }

    protected function _initMail() {
        if (!file_exists(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'mail.ini')) {
            return;
        }

        $iniMailConf = new Zend_Config_Ini(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'mail.ini');
        $mailConf = ($iniMailConf instanceof Zend_Config ? $iniMailConf->toArray() : null);

        if ($mailConf == null) {
            return;
        }

        $smtpTransportConf = array('auth' => 'login');

        if (isset($mailConf['port'])) {
            $smtpTransportConf['port'] = $mailConf['port'];
        }

        if (isset($mailConf['user'])) {
            $smtpTransportConf['username'] = $mailConf['user'];
        }

        if (isset($mailConf['password'])) {
            $smtpTransportConf['password'] = $mailConf['password'];
        }

        if (isset($mailConf['encryption'])) {
            $smtpTransportConf['ssl'] = $mailConf['encryption'];
        }

        $mailTransport = new Zend_Mail_Transport_Smtp($mailConf['host'], $smtpTransportConf);

        Zend_Mail::setDefaultTransport($mailTransport);

        if (isset($mailConf['from']) && isset($mailConf['from_name'])) {
            Zend_Mail::setDefaultFrom($mailConf['from'], $mailConf['from_name']);
        } elseif (isset($mailConf['from'])) {
            Zend_Mail::setDefaultFrom($mailConf['from']);
        }

        if (isset($mailConf['reply-to']) && isset($mailConf['reply-to_name'])) {
            Zend_Mail::setDefaultFrom($mailConf['reply-to'], $mailConf['reply-to_name']);
        } elseif (isset($mailConf['reply-to'])) {
            Zend_Mail::setDefaultFrom($mailConf['reply-to']);
        }
    }

}
