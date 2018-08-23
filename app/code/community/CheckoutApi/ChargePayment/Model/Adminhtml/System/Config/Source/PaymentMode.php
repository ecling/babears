<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_PaymentMode
 *
 * @version 20151007
 */
class CheckoutApi_ChargePayment_Model_Adminhtml_System_Config_Source_PaymentMode
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
                'value' => CheckoutApi_ChargePayment_Model_CreditCardJs::PAYMENT_MODE_MIXED,
                'label' => Mage::helper('chargepayment')->__('Mixed')
            ),
            array(
                'value' => CheckoutApi_ChargePayment_Model_CreditCardJs::PAYMENT_MODE_CARD,
                'label' => Mage::helper('chargepayment')->__('Card')
            ),
            array(
                'value' => CheckoutApi_ChargePayment_Model_CreditCardJs::PAYMENT_MODE_LOCAL_PAYMENT,
                'label' => Mage::helper('chargepayment')->__('Local Payment')
            ),
        );
    }
}