<?php

class BBBManager_Model_Group extends Zend_Db_Table_Abstract {

    protected $_name = 'group';
    protected $_primary = 'group_id';
    protected $_dependentTables = array('BBBManager_Model_GroupGroup',
        'BBBManager_Model_MeetingRoomGroup',
        'BBBManager_Model_RecordGroup',
        'BBBManager_Model_UserGroup');
    protected $_referenceMap = array(
        'AuthMode' => array(
            'columns' => 'auth_mode_id',
            'refTableClass' => 'BBBManager_Model_AuthMode',
            'refColumns' => 'auth_mode_id',
            'onDelete' => self::CASCADE_RECURSE,
            'onUpdate' => self::CASCADE_RECURSE
        )
    );
    private static $_groupInfiniteLoopControl = array();
    private static $_allGroups = array();

    public function findAll() {
        $select = $this->select();
        $select->order(array('name'));
        return $this->fetchAll($select);
    }

    /*
      public function findGroupById($id = null) {
      if ($id != null) {
      $select = $this->select()
      ->from(array($this->_name))
      ->where('group_id=?', $id);
      } else {
      $select = $this->select()
      ->from(array($this->_name));
      }
      return $this->fetchAll($select);
      }

      public function findSubgroups($id) {
      $select = $this->select()
      ->setIntegrityCheck(false)
      ->from(array($this->_name), array('group_id', 'groupName' => 'name'))
      ->join(array('group_group'), 'group_group.group_id = group.group_id AND group_group.parent_group_id=' . $id, array());

      return $this->fetchAll($select);
      }

      public function findGroupByAuth($aId, $gId = null, $rId = null, $evId = null) {

      $select = $this->select()
      ->setIntegrityCheck(false)
      ->from(array($this->_name), array('group_id',
      'name',
      'auth_mode_id',
      'linkedGroup' => new Zend_Db_Expr('false'),
      'linkedRoom' => new Zend_db_Expr('false'),
      'recordGroup' => new Zend_db_Expr('false'),
      'meeting_room_profile_id' => new Zend_Db_Expr('0')));

      if (is_null($gId) && is_null($rId) && is_null($evId)) {
      $select->where('group.auth_mode_id=?', $aId);
      } else if (!is_null($gId)) {
      $select->joinLeft(array('group_group'), 'group_group.group_id = group.group_id AND group_group.parent_group_id=' . $gId, array('linkedGroup' => new Zend_Db_Expr('CASE WHEN group_group.group_id IS NULL THEN FALSE ELSE TRUE END')));
      $select->where('group.auth_mode_id=?', $aId);
      $select->where('group.group_id <> ?', $gId);
      } else if (!is_null($rId)) {
      $select->joinLeft(array('meeting_room_group'), 'group.group_id = meeting_room_group.group_id AND meeting_room_group.meeting_room_id=' . $rId, array('linkedRoom' => new Zend_Db_expr('CASE WHEN meeting_room_group.group_id IS NULL THEN FALSE ELSE TRUE END'), 'meeting_room_profile_id'));
      $select->where('group.auth_mode_id=?', $aId);
      } else if (!is_null($evId)) {
      $select->joinLeft(array('record_group'), 'record_group.group_id=group.group_Id AND record_group.record_id=' . $evId, array('recordGroup' => new Zend_db_Expr('CASE WHEN record_group.group_id IS NULL THEN false ELSE true END')));
      $select->where('group.auth_mode_id=?', $aId);
      }

      return $this->fetchAll($select);
      }

      public function findUserGroups($uId) {
      $select = $this->select();
      $select->setIntegrityCheck(false);
      $select->from(array($this->_name)); //,array('group_id','name','linkedGroup'=> new Zend_Db_Expr('0')));
      $select->joinleft(array('user_group'), 'user_group.group_id = group.group_id AND user_group.user_id=' . $uId, array('linkedGroup' => new Zend_Db_Expr('CASE WHEN user_group.group_id IS NULL THEN false ELSE true END')));

      return $this->fetchAll($select);
      } */

    public function findLocalGroupsByLdapGroup($ldapMember = null) {
        $select = $this->select();
        $select->setIntegrityCheck(false)
                ->from('group_group', null)
                ->joinInner(array('local_groups' => 'group'), 'local_groups.group_id = group_group.parent_group_id and local_groups.auth_mode_id = ' . BBBManager_Config_Defines::$LOCAL_AUTH_MODE, array('group_id', 'name', 'access_profile_id'))
                ->joinInner(array('ldap_groups' => 'group'), 'ldap_groups.group_id = group_group.group_id and ldap_groups.auth_mode_id = ' . BBBManager_Config_Defines::$LDAP_AUTH_MODE, array('ldap_group_ids' => new Zend_Db_Expr('GROUP_CONCAT(distinct ldap_groups.group_id SEPARATOR ",")'), 'ldap_group_names' => new Zend_Db_Expr('GROUP_CONCAT(distinct ldap_groups.name SEPARATOR ";")')));

        $select->group(array('local_groups.group_id', 'local_groups.name', 'local_groups.access_profile_id'));

        if ($ldapMember != null) {
            $select->where('ldap_groups.name in (?)', $ldapMember);
        }

        return $this->fetchAll($select);
    }

    public function findLdapGroups($groupNamesFromLdap = null) {
        $select = $this->select();
        $select->where('auth_mode_id = ?', BBBManager_Config_Defines::$LDAP_AUTH_MODE);

        if ($groupNamesFromLdap != null && (is_array($groupNamesFromLdap)) && (count($groupNamesFromLdap) > 0)) {
            $select->where('name in (?)', $groupNamesFromLdap);
        }

        return $this->fetchAll($select);
    }

    public function getGroupHierarchy() {
        /* $groupsWithoutChildsSelect = $this->select()->setIntegrityCheck(false);
          $groupsWithoutChildsSelect->from('group')
          ->joinLeft('group_group', 'group_group.parent_group_id = group.group_id', null)
          ->where('group_group.group_id is null');

          $groupsWithoutChilds = $this->fetchAll($groupsWithoutChildsSelect);
          $rGroupsWithoutChilds = ($groupsWithoutChilds != null ? $groupsWithoutChilds->toArray() : array()); */

        /* if (count($rGroupsWithoutChilds) == 0) {
          throw new Exception('grupos invalidos');
          } */

        $groupInheritanceSelect = $this->select()->setIntegrityCheck(false);
        $groupInheritanceSelect->from('group', null)
                ->join('group_group', 'group_group.group_id = group.group_id', array('group_id', 'parent_group_id'));

        $groupsInheritance = $this->fetchAll($groupInheritanceSelect);
        $rGroupsInheritance = ($groupsInheritance != null ? $groupsInheritance->toArray() : array());

        /* if (count($rGroupsInheritance) == 0) {
          throw new Exception('grupos invalidos II');
          } */

        $allGroupsSelect = $this->select()->order('group_id');
        $allGroups = $this->fetchAll($allGroupsSelect);
        $rAllGroupsAux = ($allGroups != null ? $allGroups->toArray() : array());

        foreach ($rAllGroupsAux as $group) {
            self::$_allGroups[$group['group_id']] = $group;
        }

        $child_x_parent = array();

        foreach ($rGroupsInheritance as $rInheritanceRule) {
            if (!isset($child_x_parent[$rInheritanceRule['group_id']])) {
                $child_x_parent[$rInheritanceRule['group_id']] = array();
            }
            $child_x_parent[$rInheritanceRule['group_id']][] = $rInheritanceRule['parent_group_id'];
        }

        $groupsHierarchy = $this->buildGroupHierarchy($child_x_parent);

        foreach ($groupsHierarchy as $groupId => $group) {
            foreach ($group['parents'] as $parent) {
                $parentId = $parent['group_id'];
                if (!isset($groupsHierarchy[$groupId]['access_profile_id'])) {
                    $groupsHierarchy[$groupId]['access_profile_id'] = array();
                }

                if (isset(self::$_allGroups[$parentId]['access_profile_id'])) {
                    $groupsHierarchy[$groupId]['access_profile_id'][] = self::$_allGroups[$parentId]['access_profile_id'];
                }
            }

            $groupsHierarchy[$groupId]['access_profile_id'][] = self::$_allGroups[$groupId]['access_profile_id'];
            $groupsHierarchy[$groupId]['access_profile_id'] = array_unique($groupsHierarchy[$groupId]['access_profile_id']);
        }

        //var_dump($groupsHierarchy);die();

        return $groupsHierarchy;
    }

    private function buildGroupHierarchy($childXParentMapping) {
        $groupsTree = array();

        foreach (self::$_allGroups as $groupId => $group) {
            $groupParents = array();

            if (isset($childXParentMapping[$groupId])) {
                foreach ($childXParentMapping[$groupId] as $eachParent) {
                    $this->buildGroupHierarchyRecursively($eachParent, $childXParentMapping, $groupParents);
                    //$groupsTree[$groupId]['parents'] = $this->buildGroupHierarchyRecursively($eachParent, $childXParentMapping);
                    $groupsTree[$groupId]['parents'] = $groupParents;
                    $groupsTree[$groupId]['name'] = self::$_allGroups[$groupId]['name'];
                }
            }
        }

        return $groupsTree;
    }

    private function buildGroupHierarchyRecursively($parentId, $mapping, &$currentParents) {
        $currentParents[] = array('group_id' => $parentId, 'name' => self::$_allGroups[$parentId]['name'], 'access_profile_id' => self::$_allGroups[$parentId]['access_profile_id'], 'auth_mode_id' => self::$_allGroups[$parentId]['auth_mode_id']);

        if (isset(self::$_groupInfiniteLoopControl[$parentId])) {
            return;
        }
        self::$_groupInfiniteLoopControl[$parentId] = 1;

        if (isset($mapping[$parentId])) {
            foreach ($mapping[$parentId] as $eachParent) {
                $this->buildGroupHierarchyRecursively($eachParent, $mapping, $currentParents);
                /* foreach ($parentEachParent as $item) {
                  $currentParents[] = $item;
                  } */
            }
        }
        return $currentParents;
    }

    public function deleteById($id) {
        if (is_array($id)) {
            $where = $this->getDefaultAdapter()->quoteInto('group_id in (?)', $id);
        } else {
            $where = $this->getDefaultAdapter()->quoteInto('group_id = ?', $id);
        }

        return $this->delete($where);
    }

    public function importCsv($fileContents) {
        $csvRecords = IMDT_Util_Csv::import($fileContents);
        $cols = $this->info('cols');
        $pk = (is_array($this->_primary) ? current($this->_primary) : $this->_primary);

        $validRecords = array();

        foreach ($csvRecords as $record) {
            $validRecord = array();
            foreach ($record as $column => $value) {
                /* if($column == $pk){
                  continue;
                  } */

                if (in_array($column, array($pk, 'auth_mode_id', 'access_profile_id')) !== false) {
                    continue;
                }

                if (array_search($column, $cols) !== false) {
                    if ($value == '') {
                        $value = NULL;
                    } elseif ($column == 'name') {
                        if (mb_detect_encoding($value) == 'UTF-8') {
                            $value = utf8_decode($value);
                        }
                        $validRecord[$column] = IMDT_Util_String::camelize($value);
                    } else {
                        $validRecord[$column] = $value;
                    }
                }
            }

            $validRecord['auth_mode_id'] = BBBManager_Config_Defines::$LOCAL_AUTH_MODE;
            $validRecord['access_profile_id'] = BBBManager_Config_Defines::$SYSTEM_USER_PROFILE;

            $validRecords[] = $validRecord;
        }

        $recordCount = 0;

        foreach ($validRecords as $iRecord => $record) {
            $existsSelect = $this->select();
            $existsSelect->where('name = ?', $record['name']);

            $exists = $this->fetchRow($existsSelect);

            if ($exists != null) {
                throw new Exception(sprintf(IMDT_Util_Translate::_('Invalid CSV file, record in line %s already exists.'), ($iRecord + 1)));
            }

            $this->insert($record);
            $recordCount++;
        }

        return $recordCount;
    }

}
