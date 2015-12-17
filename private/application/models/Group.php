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
