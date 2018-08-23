<?php

/**
 * Resource Model for Saved credit cards
 *
 * Class CheckoutApi_ChargePayment_Model_Resource_Customercard
 *
 * @version 20151026
 */
class CheckoutApi_ChargePayment_Model_Resource_Customercard extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('chargepayment/customercard', 'entity_id');
    }
}