<?php
/**
 * Collection Model for Saved credit cards
 *
 * Class CheckoutApi_ChargePayment_Model_Resource_Customercard_Collection
 *
 * @version 20151026
 */

class CheckoutApi_ChargePayment_Model_Resource_Customercard_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('chargepayment/customercard');
    }
}