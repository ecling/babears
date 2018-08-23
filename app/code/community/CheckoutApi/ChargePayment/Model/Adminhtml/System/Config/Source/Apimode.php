<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_ApiMode
 *
 * @version 20151007
 */
class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_ApiMode
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
                'value' => CheckoutApi_ChargePayment_Helper_Data::API_MODE_SANDBOX,
                'label' => Mage::helper('chargepayment')->__('SandBox')
            ),
            array(
                'value' => CheckoutApi_ChargePayment_Helper_Data::API_MODE_LIVE,
                'label' => Mage::helper('chargepayment')->__('Live')
            ),
        );
    }
}