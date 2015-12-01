<?php

class IMDT_Controller_Helper_ContextSwitch extends Zend_Controller_Action_Helper_ContextSwitch {

    protected $_autoSerialization = true;
    // TODO: run through Zend_Serializer::factory()
    protected $_availableAdapters = array(
        'json' => 'Zend_Serializer_Adapter_Json',
        'xml' => 'IMDT_Serializer_Adapter_Xml',
        'php' => 'Zend_Serializer_Adapter_PhpSerialize'
    );
    protected $_rest_contexts = array(
        'json' => array(
            'suffix' => 'json',
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'options' => array(
                'autoDisableLayout' => true,
            ),
            'callbacks' => array(
                'init' => 'initAbstractContext',
                'post' => 'restContext'
            ),
        ),
        'xml' => array(
            'suffix' => 'xml',
            'headers' => array(
                'Content-Type' => 'application/xml'
            ),
            'options' => array(
                'autoDisableLayout' => true,
            ),
            'callbacks' => array(
                'init' => 'initAbstractContext',
                'post' => 'restContext'
            ),
        ),
        'php' => array(
            'suffix' => 'php',
            'headers' => array(
                'Content-Type' => 'application/x-httpd-php'
            ),
            'options' => array(
                'autoDisableLayout' => true,
            ),
            'callbacks' => array(
                'init' => 'initAbstractContext',
                'post' => 'restContext'
            )
        ),
        'html' => array(
            'headers' => array(
                'Content-Type' => 'text/html; Charset=UTF-8'
            ),
            'options' => array(
                'autoDisableLayout' => false,
            )
        )
    );

    public function __construct($options = null) {
        if ($options instanceof Zend_Config) {
            $this->setConfig($options);
        } elseif (is_array($options)) {
            $this->setOptions($options);
        }

        if (empty($this->_contexts)) {
            $this->addContexts($this->_rest_contexts);
        }

        $this->init();
    }

    public function getAutoDisableLayout() {
        $context = $this->_actionController->getRequest()->getParam($this->getContextParam());
        return $this->_rest_contexts[$context]['options']['autoDisableLayout'];
    }

    public function initAbstractContext() {
        if (!$this->getAutoSerialization()) {
            return;
        }

        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $view = $viewRenderer->view;

        if ($view instanceof Zend_View_Interface) {
            $viewRenderer->setNoRender(true);
        }
    }

    public function exportHandler() {
        $outputFormat = $this->_actionController->getRequest()->getParam('export');

        if ($outputFormat == 'csv' || $outputFormat == 'pdf') {
            $controllerName = $this->_actionController->getRequest()->getControllerName();

            $arrayPath = array();
            $arrayPath[] = 'public';
            $arrayPath[] = 'export';
            $arrayPath[] = $controllerName;
            $arrayPath[] = uniqid();

            $strTargetPath = $_SERVER['DOCUMENT_ROOT'];
            foreach ($arrayPath as $curr) {
                $strTargetPath = $strTargetPath . '/' . $curr;
                if (!is_dir($strTargetPath))
                    mkdir($strTargetPath, 0777, TRUE);
            }

            if (!touch($strTargetPath)) {
                throw new Exception($strTargetPath . ' not writable');
            }


            $fileName = $controllerName . '.' . $outputFormat;
            $view = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view;

            if ($outputFormat == 'csv') {
                $openedfile = fopen($strTargetPath . '/' . $fileName, 'w');
                //fprintf($openedfile, chr(0xEF).chr(0xBB).chr(0xBF));
                //fwrite($openedfile, chr(239) . chr(187) . chr(191));
                fwrite($openedfile, "sep=,\r\n");
                $useUtf8 = $this->_actionController->getRequest()->getParam('utf8', 0);

                if (count($view->response['collection']) > 0) {
                    if ($this->_actionController->getRequest()->getHeader('columns-desc')) {
                        if (!$useUtf8) {
                            $rowHeader = iconv('UTF-8', 'ISO-8859-1', $this->_actionController->getRequest()->getHeader('columns-desc'));
                        }
                        fwrite($openedfile, $rowHeader . "\r\n");
                    } else {
                        $keys = array_keys($view->response['collection'][0]);

                        if (!$useUtf8) {
                            array_walk($keys, function(&$value, $key) {
                                $value = iconv('UTF-8', 'ISO-8859-1', $value);
                            });
                        }

                        fputcsv($openedfile, $keys);
                    }
                    //print chr(255) . chr(254) . mb_convert_encoding($tsv_data, 'UTF-16LE', 'UTF-8');
                    foreach ($view->response['collection'] as $row) {

                        if (!$useUtf8) {
                            array_walk($row, function(&$value, $key) {
                                $value = iconv('UTF-8', 'ISO-8859-1', $value);
                            });
                        }

                        fputcsv($openedfile, array_values($row));
                    }

                    fclose($openedfile);
                }
            } elseif ($outputFormat == 'pdf') {
                set_time_limit(0);
                require_once('tcpdf' . DIRECTORY_SEPARATOR . 'config/tcpdf_config.php');
                require_once('tcpdf' . DIRECTORY_SEPARATOR . 'tcpdf.php');

                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

                // set document information
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetAuthor('');
                $pdf->SetTitle('');
                $pdf->SetSubject('');
                $pdf->SetKeywords('');

                // set default header data
                $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Administração BBB', 'Exportado em:' . date('d/m/Y H:i'));

                // set header and footer fonts
                $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
                $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

                // set default monospaced font
                $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

                // set margins
                $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
                $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
                $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

                // set auto page breaks
                $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

                // set image scale factor
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

                // set font
                $pdf->SetFont('helvetica', '', 10);

                // add a page
                $pdf->AddPage();

                $htmlPrepend = '<html>';
                $htmlPrepend .= '<style>html,body{margin:0px;padding:0px;} table{width:100%;border-collpase:collapse;border:1px solid #000;} th{background-color:#eaeaea;font-weight:bold;}</style>';
                $htmlPrepend .= '<body>';
                $htmlPrepend .= '<table border="1">';

                $htmlAppend = '</table>';
                $htmlAppend .= '</body></html>';

                $tableHeaders = explode(',', $this->_actionController->getRequest()->getHeader('columns-desc'));
                $columnsFormat = $this->_actionController->getRequest()->getHeader('columns-format');
                $rColumnsFormat = array();

                if ($columnsFormat != null) {
                    $rColumnsFormat = explode(',', $columnsFormat);
                } else {
                    foreach ($tableHeaders as $item) {
                        $rColumnsFormat[] = null;
                    }
                }

                $htmlHeaders = '';

                if (count($tableHeaders) > 0) {
                    $htmlHeaders = '<tr>';
                    foreach ($tableHeaders as $tableHeader) {
                        $htmlHeaders .= sprintf('<th>%s</th>', $tableHeader);
                    }
                    $htmlHeaders .= '</tr>';
                }

                $html = $htmlPrepend . $htmlHeaders;

                if (is_array($view->response['collection']) && (count($view->response['collection']) > 0)) {
                    foreach ($view->response['collection'] as $iRow => $collectionRow) {
                        if ((($iRow > 0) && (($iRow % 32) == 0))) {
                            $html .= $htmlAppend;
                            $pdf->writeHTML($html, true, false, true, false, '');
                            $pdf->AddPage();
                            //echo $html;
                            $html = $htmlPrepend . $htmlHeaders;
                        }

                        $html .= '<tr>';
                        $iColumn = 0;
                        foreach ($collectionRow as $column) {
                            $tdContent = $column;

                            if ($rColumnsFormat[$iColumn] != null) {
                                switch ($rColumnsFormat[$iColumn]) {
                                    case 'datetime':
                                        $tdContent = IMDT_Util_Date::filterDatetimeToCurrentLang($column);
                                        break;
                                    case 'datetime-no-seconds':
                                        $tdContent = IMDT_Util_Date::filterDatetimeToCurrentLang($column, false);
                                        break;
                                }
                            }

                            $html .= sprintf('<td>%s</td>', $tdContent);
                            $iColumn++;
                        }
                        $html .= '</tr>';
                    }
                }

                $html .= $htmlAppend;

                // output the HTML content
                $pdf->writeHTML($html, true, false, true, false, '');

                // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
                //Close and output PDF document
                $pdf->Output($strTargetPath . DIRECTORY_SEPARATOR . $fileName, 'F');

                //============================================================+
                // END OF FILE
                //============================================================+
            }
            unset($view->response['collection']);
            $view->response['url'] = IMDT_Util_Url::baseUrl() . '/' . implode('/', $arrayPath) . '/' . $fileName;
        }
    }

    public function leachResponseColumns() {
        $columns = $this->_actionController->getRequest()->getHeader('columns-leach');
        $arrColumnsLeach = explode(',', $columns);


        $view = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view;

        $arrNewCollection = array();

        if (!isset($view->response['collection'])) {
            return;
        }

        foreach ($view->response['collection'] as $row) {
            $arrNewRow = array();

            foreach ($arrColumnsLeach as $currColumn) {
                $arrNewRow[$currColumn] = isset($row[$currColumn]) ? $row[$currColumn] : '';
            }
            $arrNewCollection[] = $arrNewRow;
        }

        $view->response['collection'] = $arrNewCollection;
    }

    public function restContext() {
        if (!$this->getAutoSerialization()) {
            return;
        }

        if ($this->_actionController->getRequest()->getHeader('columns-leach')) {
            $this->leachResponseColumns();
        }

        if ($this->_actionController->getRequest()->getActionName() == 'index' && $this->_actionController->getRequest()->getParam('export')) {
            $this->exportHandler();
        }

        $view = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->view;
        if ($view instanceof Zend_View_Interface) {
            if (method_exists($view, 'getVars')) {
                $data = $view->getVars();

                if (count($data) !== 0) {
                    $serializer = new $this->_availableAdapters[$this->_currentContext];
                    $body = $serializer->serialize($data);

                    if ($this->_currentContext == 'xml') {
                        $stylesheet = $this->getRequest()->getHeader('X-XSL-Stylesheet');

                        if ($stylesheet !== false and ! empty($stylesheet)) {
                            $body = str_replace('<?xml version="1.0"?>', sprintf('<?xml version="1.0"?><?xml-stylesheet type="text/xsl" href="%s"?>', $stylesheet), $body);
                        }
                    }

                    if ($this->_currentContext == 'json') {
                        $callback = $this->getRequest()->getParam('jsonp-callback', false);

                        if ($callback !== false and ! empty($callback)) {
                            $body = sprintf('%s(%s)', $callback, $body);
                        }
                    }

                    $this->getResponse()->setBody($body);
                }
            }
        }
    }

    public function setAutoSerialization($flag) {
        $this->_autoSerialization = (bool) $flag;
        return $this;
    }

    public function getAutoSerialization() {
        return $this->_autoSerialization;
    }

}
