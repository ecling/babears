<?php
/**
 * Frame for PHP API
 *
 * Class CheckoutApi_ChargePayment_Block_FramePhp
 *
 * @version 20160209
 */
class CheckoutApi_ChargePayment_Block_FramePhp  extends Mage_Core_Block_Template
{
    /**
     * Return TRUE if is PHP API
     *
     * @return bool
     *
     * @version 20160209
     */
    public function isPhpApiPaymentMethod() {
       $paymentMethod = (string)Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethod();

        return $paymentMethod === CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD ? true : false;
    }

    /**
     * Return Payment Code
     *
     * @return string
     *
     * @version 20160219
     */
    public function getPaymentCode() {
        return CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD;
    }
}