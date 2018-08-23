<?php

class Martin_Flytcloud_Model_Resource_Shipping_Type_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('flytcloud/shipping_type');
    }
    public function toOptionArray()
    {
        return $this->_toOptionArray('entity_id','shipping_type');
    }
}
