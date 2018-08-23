<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_Is3d
 *
 * @version 20151007
 */
class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_Is3d
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
                'value' => CheckoutApi_ChargePayment_Helper_Data::CREDIT_CARD_CHARGE_MODE_NOT_3D,
                'label' => Mage::helper('chargepayment')->__('No')
            ),
            array(
                'value' => CheckoutApi_ChargePayment_Helper_Data::CREDIT_CARD_CHARGE_MODE_3D,
                'label' => Mage::helper('chargepayment')->__('Yes')
            ),
        );
    }
}