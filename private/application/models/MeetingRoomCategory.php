<?php

class BBBManager_Model_MeetingRoomCategory extends Zend_Db_Table_Abstract {

    protected $_name = 'meeting_room_category';
    protected $_primary = 'meeting_room_category_id';

    public function getAllOrdered($categoriesCollection = null) {
        if ($categoriesCollection == null) {
            $categoriesCollection = $this->fetchAll();
            $categoriesCollection = $categoriesCollection->toArray();
        }

        $orderedCategories = $this->orderedCategories($categoriesCollection);
        $this->updateHierarchy($orderedCategories);

        return $orderedCategories;
    }

    public function getAllLeaf($categoriesCollection = null) {
        if ($categoriesCollection == null) {
            $categoriesCollection = $this->fetchAll();
            $categoriesCollection = $categoriesCollection->toArray();
        }

        $orderedCategories = $this->orderedCategories($categoriesCollection);
        $this->updateHierarchy($orderedCategories);

        $leafNodes = array();
        $rHierarchy = array();

        foreach ($orderedCategories as $item) {
            if (!isset($item['hierarchy'])) {
                continue;
            }

            $rHierarchy[] = $item['hierarchy'];
        }

        foreach ($orderedCategories as $k => $item) {
            if (!isset($item['hierarchy'])) {
                continue;
            }

            if (array_search($item['hierarchy'] . '-' . $item['meeting_room_category_id'], $rHierarchy) === false) {
                $leafNodes[] = $item;
            }
        }

        return $leafNodes;
    }

    public function orderedCategories($collection) {
        $rootNodes = array();
        $rootNodesNames = array();

        $orderedNodes = array();

        foreach ($collection as $item) {
            if ($item['parent_id'] == '') {
                $rootNodes[] = $item;
                $rootNodesNames[] = strtolower($item['name']);
            }
        }

        /* re-order root nodes array by name asceding */

        array_multisort($rootNodesNames, SORT_ASC, $rootNodes);

        foreach ($rootNodes as $item) {
            $orderedNodes[] = $item;

            $this->_getChildNodes($item, $collection, $orderedNodes);
        }

        $orderedNodesWithKey = array();

        foreach ($orderedNodes as $value) {
            $orderedNodesWithKey[$value['meeting_room_category_id']] = $value;
        }

        return $orderedNodesWithKey;
    }

    private function _getChildNodes($parent, $collection, &$output) {
        foreach ($collection as $itemCollection) {
            if ($itemCollection['parent_id'] == $parent['meeting_room_category_id']) {
                $output[] = $itemCollection;
                $this->_getChildNodes($itemCollection, $collection, $output);
            }
        }
    }

    public function updateHierarchy(&$collection) {
        foreach ($collection as &$item) {
            if ($item['parent_id'] != NULL) {
                $parents = array();
                $this->_getNodeHierarchy($item, $collection, $parents);

                $parentsIds = array();

                foreach ($parents as $parent) {
                    $parentsIds[] = $parent['parent_id'];
                }

                $item['hierarchy'] = implode('-', array_reverse($parentsIds));
            }
        }

        foreach ($collection as &$item) {
            if (isset($item['hierarchy'])) {
                $rHierarchy = explode('-', $item['hierarchy']);
                array_push($rHierarchy, $item['meeting_room_category_id']);
                $rPath = array();
                foreach ($rHierarchy as $pathId) {
                    $rPath[] = $collection[$pathId]['name'];
                }
                $item['path'] = implode('-', $rPath);
            }
        }
    }

    private function _getNodeHierarchy($item, $collection, &$parents) {
        if ($item['parent_id'] != NULL) {
            $parents[] = $item;
            $this->_getNodeHierarchy($collection[$item['parent_id']], $collection, $parents);
        }
    }

}
