<?php

class Martin_Flytcloud_Model_Resource_Shipping_Type_Country extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('flytcloud/shipping_type_country', 'entity_id');
    }
}

