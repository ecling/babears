<?php

class Martin_Flytcloud_Model_Shipping_Type extends Mage_Core_Model_Abstract
{
    const DEFAULT_TYPE_CODE="HKRAM";
    protected function _construct() {
        $this->_init('flytcloud/shipping_type');
    }
}
