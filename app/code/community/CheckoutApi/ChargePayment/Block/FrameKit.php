<?php
/**
 * Frame for Checkout Kit
 *
 * Class CheckoutApi_ChargePayment_Block_FrameKit
 *
 * @version 20160502
 */
class CheckoutApi_ChargePayment_Block_FrameKit  extends Mage_Core_Block_Template
{
    /**
     * Return TRUE if is Kit method
     *
     * @return bool
     *
     * @version 20160502
     */
    public function isKitPaymentMethod() {
       $paymentMethod = (string)Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethod();

        return $paymentMethod === CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_KIT ? true : false;
    }

    /**
     * Return Payment Code
     *
     * @return string
     *
     * @version 20160502
     */
    public function getPaymentCode() {
        return CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_KIT;
    }
}