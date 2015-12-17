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
}
