<?php

class BBBManager_Model_UserGroup extends Zend_Db_Table_Abstract {

    protected $_name = 'user_group';
    protected $_primary = array('user_id', 'group_id');
    protected $_referenceMap = array(
        'Group' => array(
            'columns' => 'group_id',
            'refTableClass' => 'BBBManager_Model_Group',
            'refColumns' => 'group_id',
            'onDelete' => self::CASCADE_RECURSE,
            'onUpdate' => self::CASCADE_RECURSE
        ),
        'User' => array(
            'columns' => 'user_id',
            'refTableClass' => 'BBBManager_Model_User',
            'refColumns' => 'user_id',
            'onDelete' => self::CASCADE_RECURSE,
            'onUpdate' => self::CASCADE_RECURSE)
    );

    public function findByUserId($userId) {
        $select = $this->select()->setIntegrityCheck(false);

        $select->from(array('ug' => 'proc_user_groups'), array('user_id', 'group_id'))
                ->join(array('g' => 'user'), 'g.user_id = ug.user_id', array('access_profile_id'));

        $select->where('ug.user_id = ?', $userId);

        return $this->fetchAll($select);
    }

}
