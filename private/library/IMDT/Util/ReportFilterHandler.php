<?php

class IMDT_Util_ReportFilterHandler {

    public static function parseThisFilters(&$select, $arrFilters) {
        $arrParams = array();

        $controller = Zend_Controller_Front::getInstance();
        $request = $controller->getRequest();

        foreach ($arrFilters as $paramName => $curr) {
            $value = $request->getParam($paramName, '');
            $condition = $request->getParam($paramName . '_c', 'e');

            if (is_array($value) && (count($value) == 0))
                continue;
            elseif (is_scalar($value) && (strlen(trim($value)) == 0))
                continue;

            if ($curr['type'] == 'text') {
                if ($value && strlen(trim($value)) > 0) {
                    if ($condition == 'e') {
                        $select->where('upper(' . $curr['column'] . ') like upper(?)', $value);
                    } elseif ($condition == 'd') {
                        $select->where('upper(coalesce(' . $curr['column'] . ',\'\')) not like upper(?)', $value);
                    } elseif ($condition == 'i') {
                        $select->where('upper(' . $curr['column'] . ') like upper(?)', '%' . $value . '%');
                    }
                }
            } elseif ($curr['type'] == 'integer') {
                if ($condition == 'e') {
                    $select->where($curr['column'] . ' = ' . $value);
                } elseif ($condition == 'i') {
                    if (is_array($value)) {
                        $select->where($curr['column'] . ' in (?)', $value);
                    } else {
                        $select->where($curr['column'] . ' in (' . $value . ')');
                    }
                } elseif ($condition == 'd') {
                    if (is_array($value)) {
                        $select->where($curr['column'] . ' not in (?)', $value);
                    } else {
                        $select->where($curr['column'] . ' not in (' . $value . ')');
                    }
                } elseif ($condition == 'l') {
                    $select->where($curr['column'] . ' <= ' . $value);
                } elseif ($condition == 'g') {
                    $select->where($curr['column'] . ' >= ' . $value);
                }
            } elseif ($curr['type'] == 'exists') {
                if ($condition == 'i') {
                    if (is_array($value)) {
                        $select->where(new Zend_Db_Expr('(exists (' . str_replace('#ID#', implode(',', $value), $curr['criteria']) . '))'));
                    } else {
                        $select->where(new Zend_Db_Expr('(exists (' . str_replace('#ID#', $value, $curr['criteria']) . '))'));
                    }
                } elseif ($condition == 'd') {
                    if (is_array($value)) {
                        $select->where(new Zend_Db_Expr('(not exists (' . str_replace('#ID#', implode(',', $value), $curr['criteria']) . '))'));
                    } else {
                        $select->where(new Zend_Db_Expr('(not exists (' . str_replace('#ID#', $value, $curr['criteria']) . '))'));
                    }
                }
            } elseif ($curr['type'] == 'boolean') {
                if ($condition == 'e') {
                    $select->where($curr['column'] . ' = ' . $value);
                }
            } elseif ($curr['type'] == 'date') {
                $strDate = IMDT_Util_Date::filterDateToApi($value);

                if ($condition == 'e') {
                    $select->where('DATE_FORMAT(' . $curr['column'] . ', \'%Y-%m-%d\') = ?', $strDate);
                } elseif ($condition == 'g') {
                    $select->where($curr['column'] . '  >= TIMESTAMP(?)', $strDate . ' 00:00:00');
                } elseif ($condition == 'l') {
                    $select->where($curr['column'] . ' <= TIMESTAMP(?)', $strDate . ' 23:59:59');
                } elseif ($condition == 'b') {
                    $select->where('' . $curr['column'] . ' >= TIMESTAMP(?)', $strDate . ' 00:00:00');

                    $value2 = $request->getParam($paramName . '_2');
                    $strDate2 = IMDT_Util_Date::filterDateToApi($value2);
                    if (strlen($strDate2) == 0)
                        continue;
                    $select->where($curr['column'] . ' <= TIMESTAMP(?)', $strDate2 . ' 23:59:59');
                }
            } elseif ($curr['type'] == 'datetime') {
                $strDatetime = IMDT_Util_Date::filterDatetimeToApi($value);
                if (strlen($strDatetime) != 19)
                    continue;

                if ($condition == 'e') {
                    $select->where('DATE_FORMAT(' . $curr['column'] . ', \'%Y-%m-%d %T\') = ?', $strDatetime);
                } elseif ($condition == 'g') {
                    $select->where($curr['column'] . '  >= TIMESTAMP(?)', $strDatetime);
                } elseif ($condition == 'l') {
                    $select->where($curr['column'] . ' <= TIMESTAMP(?)', $strDatetime);
                } elseif ($condition == 'b') {
                    $select->where($curr['column'] . ' >= TIMESTAMP(?)', $strDatetime);

                    $value2 = $request->getParam($paramName . '_2');
                    $strDatetime2 = IMDT_Util_Date::filterDatetimeToApi($value2);
                    if (strlen($strDatetime2) == 0)
                        continue;
                    $select->where($curr['column'] . ' <= TIMESTAMP(?)', $strDatetime2);
                }
            } elseif ($curr['type'] == 'ip_address') {
                if ($value && strlen(trim($value)) > 0) {
                    if ($condition == 'e') {
                        $select->where('upper(' . $curr['column'] . ') like upper(?)', $value);
                    } elseif ($condition == 'd') {
                        $select->where('upper(' . $curr['column'] . ') not like upper(?)', $value);
                    } elseif ($condition == 'i') {
                        if (preg_match('/^\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}\/\d{1,2}$/', $value)) {
                            list($ip, $mask) = explode('/', $value);
                            $select->where('INET_ATON(' . $curr['column'] . ') >= INET_ATON("' . $ip . '")');
                            $select->where('INET_ATON(' . $curr['column'] . ') <= INET_ATON("' . $ip . '") + (pow(2, (32-' . $mask . '))-1)');
                        } else {
                            $select->where($curr['column'] . ' like ?', '%' . $value . '%');
                        }
                    }
                }
            }
        }

        return true;
    }

    public static function parseThisQueries(&$select, $arrFilters) {
        $arrParams = array();

        $controller = Zend_Controller_Front::getInstance();
        $request = $controller->getRequest();

        $query = $request->getParam('q', array());
        $musthave = $request->getParam('musthave', 'all');

        //debug($query, false);

        $arrWhereGroups = array();

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        if (count($query) > 0) {
            foreach ($query as $curr) {
                $name = $curr['n'];
                $value = $curr['v'];
                $condition = $curr['c'];
                $until = isset($curr['u']) ? $curr['u'] : '';

                if (!in_array($name, array_keys($arrFilters)))
                    continue;
                if (is_array($value) && (count($value) == 0))
                    continue;
                elseif (!in_array($condition, array('empty', 'nempty')) && is_scalar($value) && (strlen(trim($value)) == 0))
                    continue;

                $curr = $arrFilters[$name];

                if ($curr['type'] == 'text' || $curr['type'] == 'ip_address') {
                    if ($condition == 'is') { //Is
                        $arrWhereGroups[] = $db->quoteInto('upper(' . $curr['column'] . ') like upper(?)', $value);
                    } elseif ($condition == 'isnt') { //Isn't
                        $arrWhereGroups[] = $db->quoteInto('upper(coalesce(' . $curr['column'] . ',\'\')) not like upper(?)', $value);
                    } elseif ($condition == 'in') { //Contains
                        $arrWhereGroups[] = $db->quoteInto('upper(' . $curr['column'] . ') like upper(?)', '%' . $value . '%');
                    } elseif ($condition == 'nin') { //Doesn't contain
                        $arrWhereGroups[] = $db->quoteInto('upper(coalesce(' . $curr['column'] . ',\'\')) not like upper(?)', '%' . $value . '%');
                    } elseif ($condition == 'bw') { //Begin with
                        $arrWhereGroups[] = $db->quoteInto('upper(' . $curr['column'] . ') like upper(?)', $value . '%');
                    } elseif ($condition == 'ew') { //End with
                        $arrWhereGroups[] = $db->quoteInto('upper(' . $curr['column'] . ') like upper(?)', '%' . $value);
                    } elseif ($condition == 'empty') { //Is empty
                        $arrWhereGroups[] = $db->quoteInto('coalesce(' . $curr['column'] . ',\'\') = \'\'', null);
                    } elseif ($condition == 'nempty') { //Isn't empty
                        $arrWhereGroups[] = $db->quoteInto('coalesce(' . $curr['column'] . ',\'\') != \'\'', null);
                    }
                } elseif ($curr['type'] == 'integer') {
                    /*
                      if($condition == 'is') { //Is
                      $arrWhereGroups[] = $db->quoteInto($curr['column'].' = ?',$value);
                      } elseif($condition == 'isnt') { //Isn't
                      $arrWhereGroups[] = $db->quoteInto($curr['column'].' != ?',$value);
                     */
                    if ($condition == 'gt') { //Is greater than
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' >= ?', $value);
                    } elseif ($condition == 'lt') { //Is less than
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' <= ?', $value);
                    } elseif ($condition == 'nin' || $condition == 'isnt') { //Doesn't contain
                        if (is_array($value)) {
                            $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' not in (?)', $value);
                        } else {
                            $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' not in (' . $value . ')', null);
                        }
                    } elseif ($condition == 'in' || $condition == 'is') { //Contains
                        if (is_array($value)) {
                            $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' in (?)', $value);
                        } else {
                            $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' in (' . $value . ')', null);
                        }
                    } elseif ($condition == 'empty') { //Is empty
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' is null', null);
                    } elseif ($condition == 'nempty') { //Isn't empty
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' is not null', null);
                    }
                } elseif ($curr['type'] == 'hash') {
                    if (is_array($curr['column'])) {
                        $columns = implode(',\'#\',', $curr['column']);
                        $column = 'substr(md5(concat(' . $columns . ')),1,8)';
                    } else {
                        $column = 'substr(md5(' . $curr['column'] . '),1,8)';
                    }

                    //$value = str_replace(',', '\',\'', $value);
                    $value = explode(',', $value);

                    if ($condition == 'nin' || $condition == 'isnt') { //Doesn't contain
                        $arrWhereGroups[] = $db->quoteInto($column . ' not in (?)', $value);
                    } elseif ($condition == 'in' || $condition == 'is') { //Contains
                        $arrWhereGroups[] = $db->quoteInto($column . ' in (?)', $value);
                    } elseif ($condition == 'empty') { //Is empty
                        $arrWhereGroups[] = $db->quoteInto($column . ' is null', null);
                    } elseif ($condition == 'nempty') { //Isn't empty
                        $arrWhereGroups[] = $db->quoteInto($column . ' is not null', null);
                    }
                } elseif ($curr['type'] == 'exists') {
                    if ($condition == 'is') { //Is
                        $arrWhereGroups[] = 'exists (' . $curr['select'] . ' and ' . $curr['column'] . ' in (' . $value . '))';
                    } elseif ($condition == 'isnt') { //Isn't
                        $arrWhereGroups[] = 'not exists (' . $curr['select'] . ' and ' . $curr['column'] . ' in (' . $value . '))';
                    } elseif ($condition == 'empty') { //Is empty
                        $arrWhereGroups[] = 'not exists (' . $curr['select'] . ')';
                    } elseif ($condition == 'nempty') { //Isn't empty
                        $arrWhereGroups[] = 'exists (' . $curr['select'] . ')';
                    }
                } elseif ($curr['type'] == 'exists_key') {
                    if ($condition == 'is') { //Is
                        $arrWhereGroups[] = 'exists (' . $curr['select'] . ' and ' . $curr['column'] . ' in (' . $value . '))';
                    } elseif ($condition == 'isnt') { //Isn't
                        $arrWhereGroups[] = 'not exists (' . $curr['select'] . ' and ' . $curr['column'] . ' in (' . $value . '))';
                    } elseif ($condition == 'empty') { //Is empty
                        $arrWhereGroups[] = 'exists (' . $curr['select'] . ' and coalesce(' . $curr['column'] . ',\'\') = \'\')';
                    } elseif ($condition == 'nempty') { //Isn't empty
                        $arrWhereGroups[] = 'exists (' . $curr['select'] . ' and coalesce(' . $curr['column'] . ',\'\') != \'\')';
                    }
                } elseif ($curr['type'] == 'exists_text') {
                    if ($condition == 'is') { //Is
                        $arrWhereGroups[] = 'exists (' . $curr['select'] . ' and ' . $curr['column'] . ' = \'' . $value . '\')';
                    } elseif ($condition == 'isnt') { //Isn't
                        $arrWhereGroups[] = 'not exists (' . $curr['select'] . ' and ' . $curr['column'] . ' = \'' . $value . '\')';
                    } elseif ($condition == 'in') { //Contains
                        $arrWhereGroups[] = 'exists (' . $curr['select'] . ' and ' . $curr['column'] . ' like \'%' . $value . '%\')';
                    } elseif ($condition == 'nin') { //Doesn't contain
                        $arrWhereGroups[] = 'not exists (' . $curr['select'] . ' and ' . $curr['column'] . ' like \'%' . $value . '%\')';
                    } elseif ($condition == 'bw') { //Begin with
                        $arrWhereGroups[] = 'exists (' . $curr['select'] . ' and ' . $curr['column'] . ' like \'' . $value . '%\')';
                    } elseif ($condition == 'ew') { //End with
                        $arrWhereGroups[] = 'exists (' . $curr['select'] . ' and ' . $curr['column'] . ' like \'%' . $value . '\')';
                    } elseif ($condition == 'empty') { //Is empty
                        $arrWhereGroups[] = 'exists (' . $curr['select'] . ' and coalesce(' . $curr['column'] . ',\'\') = \'\')';
                    } elseif ($condition == 'nempty') { //Isn't empty
                        $arrWhereGroups[] = 'exists (' . $curr['select'] . ' and coalesce(' . $curr['column'] . ',\'\') != \'\')';
                    }
                } elseif ($curr['type'] == 'boolean') {
                    if ($condition == 'is') { //Is
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' = ?', $value);
                    } elseif ($condition == 'empty') { //Is empty
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' is null', null);
                    } elseif ($condition == 'nempty') { //Isn't empty
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' is not null', null);
                    }
                } elseif ($curr['type'] == 'date') {
                    $strDate = IMDT_Util_Date::filterDateToApi($value);

                    if ($condition == 'is') { //Is
                        $arrWhereGroups[] = $db->quoteInto('DATE_FORMAT(' . $curr['column'] . ', \'%Y-%m-%d\') = ?', $strDate);
                    } elseif ($condition == 'isnt') { //Isn't
                        $arrWhereGroups[] = $db->quoteInto('DATE_FORMAT(' . $curr['column'] . ', \'%Y-%m-%d\') != ?', $strDate);
                    } elseif ($condition == 'aft') { //Is after
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . '  >= TIMESTAMP(?)', $strDate . ' 00:00:00');
                    } elseif ($condition == 'bef') { //Is before
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' <= TIMESTAMP(?)', $strDate . ' 23:59:59');
                    } elseif ($condition == 'b') {
                        $strDate2 = IMDT_Util_Date::filterDateToApi($until);
                        if (strlen($strDate2) > 0) {
                            $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' between TIMESTAMP(\'' . $strDate . ' 00:00:00' . '\') and TIMESTAMP(\'' . $strDate2 . ' 23:59:59' . '\')', null);
                        } else {
                            $arrWhereGroups[] = $db->quoteInto('' . $curr['column'] . ' >= TIMESTAMP(?)', $strDate . ' 00:00:00');
                        }
                    } elseif ($condition == 'empty') { //Is empty
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' is null', null);
                    } elseif ($condition == 'nempty') { //Isn't empty
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' is not null', null);
                    }
                } elseif ($curr['type'] == 'datetime') {
                    $strDatetime = IMDT_Util_Date::filterDatetimeToApi($value);
                    if (strlen($strDatetime) != 19)
                        continue;

                    if ($condition == 'is') { //Is
                        $arrWhereGroups[] = $db->quoteInto('DATE_FORMAT(' . $curr['column'] . ', \'%Y-%m-%d %T\') = ?', $strDatetime);
                    } elseif ($condition == 'isnt') { //Isn't
                        $arrWhereGroups[] = $db->quoteInto('DATE_FORMAT(' . $curr['column'] . ', \'%Y-%m-%d %T\') != ?', $strDatetime);
                    } elseif ($condition == 'aft') { //Is after
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . '  >= TIMESTAMP(?)', $strDatetime);
                    } elseif ($condition == 'bef') { //Is before
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' <= TIMESTAMP(?)', $strDatetime);
                    } elseif ($condition == 'b') {
                        $strDatetime2 = IMDT_Util_Date::filterDatetimeToApi($until);

                        if (strlen($strDatetime2) > 0) {
                            if(isset($curr['column_until'])){ //one more magic, one less logic Kappa
                                $arrWhereGroups[] = $db->quoteInto(
                                    '('.$curr['column'].' <= DATE_ADD(TIMESTAMP(\'' . $strDatetime2 . '\'), INTERVAL 59 SECOND) ) and ('.$curr['column_until'].' >= TIMESTAMP(\'' . $strDatetime . '\') )'
                                    , null);
                            } else {
                                $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' between TIMESTAMP(\'' . $strDatetime . '\') and DATE_ADD(TIMESTAMP(\'' . $strDatetime2 . '\'), INTERVAL 59 SECOND)', null);
                            }
                        } else {
                            $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' >= TIMESTAMP(?)', $strDatetime);
                        }
                    } elseif ($condition == 'empty') { //Is empty
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' is null', null);
                    } elseif ($condition == 'nempty') { //Isn't empty
                        $arrWhereGroups[] = $db->quoteInto($curr['column'] . ' is not null', null);
                    }
                    /*
                      } elseif($curr['type'] == 'ip_address') {
                      if($value && strlen(trim($value)) > 0) {
                      if($condition == 'e') {
                      $arrWhereGroups[] = $db->quoteInto('upper('.$curr['column'].') like upper(?)',$value);
                      } elseif($condition == 'd') {
                      $arrWhereGroups[] = $db->quoteInto('upper('.$curr['column'].') not like upper(?)',$value);
                      } elseif($condition == 'i') {
                      if(preg_match('/^\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}\/\d{1,2}$/', $value)) {
                      list($ip, $mask) = explode('/', $value);
                      $arrWhereGroups[] = $db->quoteInto('INET_ATON('.$curr['column'].') >= INET_ATON("'.$ip.'") and INET_ATON('.$curr['column'].') <= INET_ATON("'.$ip.'") + (pow(2, (32-'.$mask.'))-1)');
                      } else {
                      $arrWhereGroups[] = $db->quoteInto($curr['column'].' like ?','%'.$value.'%');
                      }
                      }
                      }
                     * */
                }
            }

            //debug($arrWhereGroups);

            if (count($arrWhereGroups) > 0) {
                if ($musthave == 'one') {
                    $select->where(new Zend_Db_Expr('(' . implode(' OR ', $arrWhereGroups) . ')'));
                } else {
                    $select->where(new Zend_Db_Expr('(' . implode(' AND ', $arrWhereGroups) . ')'));
                }
            }
        }

        return true;
    }

}
