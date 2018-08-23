<?php
/**
 * Frame for js API
 *
 * Class CheckoutApi_ChargePayment_Block_Frame
 *
 * @version 20160203
 */
class CheckoutApi_ChargePayment_Block_Frame  extends Mage_Core_Block_Template
{
    /**
     * Return TRUE if is JS API
     *
     * @return bool
     *
     * @version 20160203
     */
    public function isJsApiPaymentMethod() {
       $paymentMethod = (string)Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethod();

        return $paymentMethod === CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_JS ? true : false;
    }

    /**
     * Return Payment Code
     *
     * @return string
     *
     * @version 20160219
     */
    public function getPaymentCode() {
        return CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_JS;
    }
}