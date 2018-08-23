<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_WidgetIconSize
 *
 * @version 20151007
 */
class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_WidgetIconSize
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
                'value' => 'small',
                'label' => Mage::helper('chargepayment')->__('Small')
            ),
            array(
                'value' => 'medium',
                'label' => Mage::helper('chargepayment')->__('Medium')
            ),
            array(
                'value' => 'large',
                'label' => Mage::helper('chargepayment')->__('Large')
            ),
        );
    }
}