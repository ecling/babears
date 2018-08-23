<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_OverlayShade
 *
 * @version 20151007
 */
class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_OverlayShade
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
                'value' => 'dark',
                'label' => Mage::helper('chargepayment')->__('Dark')
            ),
            array(
                'value' => 'light',
                'label' => Mage::helper('chargepayment')->__('Light')
            ),
        );
    }
}