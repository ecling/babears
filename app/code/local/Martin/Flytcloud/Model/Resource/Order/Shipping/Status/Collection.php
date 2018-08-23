<?php

class Martin_Flytcloud_Model_Resource_Order_Shipping_Status_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract{
    protected function _construct()
    {
        $this->_init('flytcloud/order_shipping_status');
    }
}
