<?php

class BBBManager_Model_GroupGroup extends Zend_Db_Table_Abstract {

    protected $_name = 'group_group';
    protected $_primary = array('group_id', 'parent_group_id');
    protected $_referenceMap = array(
        'Group' => array(
            'columns' => 'group_id',
            'refTableClass' => 'BBBManager_Model_Group',
            'refColumns' => 'group_id',
            'onDelete' => self::CASCADE_RECURSE,
            'onUpdate' => self::CASCADE_RECURSE
        ),
        'Parent' => array(
            'columns' => 'parent_group_id',
            'refTableClass' => 'BBBManager_Model_Group',
            'refColumns' => 'group_id',
            'onDelete' => self::CASCADE_RECURSE,
            'onUpdate' => self::CASCADE_RECURSE
        )
    );

    /* public function authmode($id) {

      $select = $this->select()
      ->distinct()
      ->setIntegrityCheck(false)
      ->from(array($this->_name), array('parent_auth_mode_id'))
      ->where('parent_group_id = ?', $id);

      return $this->fetchAll($select);
      } */

    public function deleteLdapGroupHierarchy($childGroupId) {
        if ($childGroupId == null) {
            return;
        }

        $select = $this->select();
        $select->where('auth_mode_id = ?', BBBManager_Config_Defines::$LDAP_AUTH_MODE);
        $select->where('parent_auth_mode_id = ?', BBBManager_Config_Defines::$LDAP_AUTH_MODE);
        $select->where('group_id = ?', $childGroupId);

        $select = $this->select()->from('group_group')
            ->join('group', 'group.group_id = group_group.group_id', null)
            ->join(array('parent_group' => 'group'), 'parent_group.group_id = group_group.parent_group_id', array('parent_auth_mode_id' => 'auth_mode_id'));
        $select->where('group.auth_mode_id = ?', BBBManager_Config_Defines::$LDAP_AUTH_MODE);
        $select->where('parent_auth_mode_id = ?', BBBManager_Config_Defines::$LDAP_AUTH_MODE);
        $select->where('group_group.group_id = ?', $childGroupId);

        $rWhere = $select->getPart(Zend_Db_Select::WHERE);

        $where = implode(' ', $rWhere);
        /* echo '<pre>';
          var_dump($where);
          echo '</pre>';
          die; */

        return $this->delete($where);
    }

}
