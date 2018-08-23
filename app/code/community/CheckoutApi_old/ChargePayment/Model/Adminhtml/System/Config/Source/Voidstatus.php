<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_Voidstatus
 *
 * @version 20151203
 */
class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_Voidstatus
{
    /**
     * Decorate select in System Configuration
     *
     * @return array
     *
     * @version
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 0,
                'label' => Mage::helper('chargepayment')->__("Don't change")
            ),
            array(
                'value' => 1,
                'label' => Mage::helper('chargepayment')->__('Cancelled')
            ),
        );
    }
}