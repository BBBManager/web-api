<?php

class Api_RecordTagsController extends Zend_Rest_Controller {

    protected $_id;
    public $filters = array();
    protected $columnValidators;

    public function init() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();

        $this->_id = $this->_getParam('id', null);

        $this->model = new BBBManager_Model_RecordTag();

        $this->columnValidators = array();

        $optUkExclude = null;
        if ($this->_id != null) {
            $optUkExclude = new Zend_Db_Expr("record_tag_id != " . $this->_id);
        }

        $this->columnValidators['name'] = array(new Zend_Validate_NotEmpty()/* ,
                  new Zend_Validate_Db_NoRecordExists(
                  array(
                  'table' => 'record_tag',
                  'field' => 'name',
                  'exclude' => $optUkExclude
                  )) */);

        $this->columnValidators['start_time'] = array(
            new Zend_Validate_Regex(
                    array(
                'pattern' => '/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])(.[0-9]{3})$/'
                    )
            )
        );

        $this->filters['name'] = array('column' => 'name', 'type' => 'text');
        $this->filters['record_id'] = array('column' => 'record_id', 'type' => 'integer');

        $this->acessLog();
    }

    public function acessLog() {
        $old = null;
        $desc = '';
        if ($this->_getParam('id', false)) {
            $this->getAction();
            if ($this->view->response['success'] == '1') {
                if (in_array($this->getRequest()->getActionName(), array('delete', 'put'))) {
                    $old = $this->view->response['row'];
                }
                //$desc = $this->view->response['row']['user_id'];
                $desc = '';
            }
            $this->view->response = null;
        }

        IMDT_Util_Log::write($desc, $old);
    }

    public function deleteAction() {
        try {
            if (strpos($this->_id, ',') == -1) {
                $rowModel = $this->model->find($this->_id)->current();
                if ($rowModel == null)
                    throw new Exception(sprintf($this->_helper->translate('Tag %s not found.'), $this->_id));

                $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('The tag was deleted successfully.'));
                $rowModel->delete();
            } else {
                $collection = $this->model->find(explode(',', $this->_id));
                $i = 0;
                foreach ($collection as $row) {
                    $row->delete();
                    $i++;
                }

                $this->view->response = array('success' => '1', 'msg' => sprintf($this->_helper->translate('%s tags was deleted successfully.'), $i));
            }
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function getAction() {
        try {
            $rowModel = $this->model->find($this->_id)->current();
            if ($rowModel == null)
                throw new Exception(sprintf($this->_helper->translate('Tag %s not found.'), $this->_id));

            $this->rowHandler($rowModel);

            $arrResponse = array();
            $arrResponse['row'] = $rowModel;
            $arrResponse['success'] = '1';
            $arrResponse['msg'] = $this->_helper->translate('Tag retrieved successfully.');

            $this->view->response = $arrResponse;
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function headAction() {
        //$this->getResponse()->appendBody("From headAction()");
    }

    public function indexAction() {
        try {
            $select = $this->model->select();
            $select->order('record_tag_id');

            IMDT_Util_ReportFilterHandler::parseThisFilters($select, $this->filters);
            IMDT_Util_ReportFilterHandler::parseThisQueries($this->select, $this->filters);

            $collection = $this->model->fetchAll($select);
            $rCollection = ($collection != null ? $collection->toArray() : array());

            $this->view->response = array('success' => '1', 'collection' => $rCollection, 'msg' => sprintf($this->_helper->translate('%s tags retrieved successfully.'), count($rCollection)));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function parseValidators($data) {
        $arrErrorMessages = array();

        foreach ($this->columnValidators as $column => $validators) {
            foreach ($validators as $currValidator) {
                $value = $data[$column] ? : '';
                $originalValue = $data[$column] ? : '';

                if ($currValidator instanceof Zend_Validate_LessThan) {
                    if (isset($data[$column . '_to_validation_value'])) {
                        $value = $data[$column . '_to_validation_value'];
                    }
                }

                if (!$currValidator->isValid($value)) {
                    foreach ($currValidator->getMessages() as $errorMessage) {
                        if ($value != $originalValue) {
                            $errorMessage = preg_replace('/\'' . $value . '\'/', $originalValue, $errorMessage);

                            if ($currValidator instanceof Zend_Validate_LessThan) {
                                $errorMessage = preg_replace('/\'' . $currValidator->getMax() . '\'/', IMDT_Util_Time::millisecondsTohhmmssmil($currValidator->getMax()), $errorMessage);
                            }
                        }

                        $arrErrorMessages[] = $this->_helper->translate('column-record_tag-' . $column) . ': ' . $errorMessage;
                    }
                    break;
                }
            }
        }

        return $arrErrorMessages;
    }

    public function doConversions(&$row) {
        $row['start_time'] = IMDT_Util_Time::hhmmssmilToMilliseconds($row['start_time']);
    }

    public function postAction() {
        $this->model->getAdapter()->beginTransaction();

        try {
            $data = $this->_helper->params();
            if (empty($data))
                throw new Exception($this->_helper->translate('Invalid Request.'));

            if (isset($data['record_id']) && ($data['record_id'] != '')) {
                $modelRecord = new BBBManager_Model_Record();
                $recordRow = $modelRecord->find($data['record_id'])->current();

                $recordingStart = strtotime($recordRow->date_start);
                $recordingEnd = strtotime($recordRow->date_end);

                $recordingDuration = (($recordingEnd - $recordingStart) * 1000);

                $data['start_time_to_validation_value'] = IMDT_Util_Time::hhmmssmilToMilliseconds($data['start_time']);

                $currentStartTimeValidators = $this->columnValidators['start_time'];
                $currentStartTimeValidators[] = new Zend_Validate_LessThan(array('max' => $recordingDuration));
                $this->columnValidators['start_time'] = $currentStartTimeValidators;
            }

            $arrErrorMessages = $this->parseValidators($data);

            if (count($arrErrorMessages) > 0) {
                $this->view->response = array('success' => '0', 'msg' => $arrErrorMessages);
                return;
            }

            $rowModel = $this->model->createRow();
            $columns = $this->model->info('cols');
            $rowValidColumns = array_flip($columns);

            foreach ($data as $field => $value) {
                if (isset($rowValidColumns[$field])) {
                    if (strlen($value) == 0) {
                        $rowModel->$field = null;
                    } else {
                        $rowModel->$field = $value;
                    }
                }
            }

            $this->doConversions($rowModel);
            $newRowId = $rowModel->save();

            $this->model->getAdapter()->commit();
            $this->view->response = array('success' => '1', 'msg' => '', 'id' => $newRowId, 'msg' => $this->_helper->translate('Tag has been created successfully.'));
        } catch (Exception $e) {
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

    public function putAction() {
        $this->model->getAdapter()->beginTransaction();

        try {
            if ($this->_id == null)
                $this->forward('post');

            $data = $this->_helper->params();
            if (empty($data))
                throw new Exception($this->_helper->translate('Invalid Request.'));

            $arrErrorMessages = $this->parseValidators($data);
            if (count($arrErrorMessages) > 0) {
                $this->view->response = array('success' => '0', 'msg' => $arrErrorMessages);
                return;
            }

            $rowModel = $this->model->find($this->_id)->current();
            if ($rowModel == null)
                throw new Exception($this->_helper->translate('Tag %s not found.'));

            $columns = $this->model->info('cols');
            $rowValidColumns = array_flip($columns);

            foreach ($data as $field => $value) {
                if (isset($rowValidColumns[$field])) {
                    if (strlen($value) == 0) {
                        $rowModel->$field = null;
                    } else {
                        $rowModel->$field = $value;
                    }
                }
            }

            $this->doConversions($rowModel);
            $rowModel->save();

            $this->model->getAdapter()->commit();
            $this->view->response = array('success' => '1', 'msg' => $this->_helper->translate('Tag has been successfully changed.'));
        } catch (Exception $e) {
            $this->model->getAdapter()->rollBack();
            $this->view->response = array('success' => '0', 'msg' => $e->getMessage());
        }
    }

}
