<?php
/**
 * Frame for js API
 *
 * Class CheckoutApi_ChargePayment_Block_Default
 *
 * @version 20160518
 */
class CheckoutApi_ChargePayment_Block_Default  extends Mage_Core_Block_Template
{

    protected $_paymentCode = CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_JS;

    /**
     * Return shade for overlayShade param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getOverlayShade() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'overlay_shade');
    }

    /**
     * Return opacity for overlayOpacity param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getOverlayOpacity() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'overlay_opacity');
    }

    /**
     * Return Customer Email
     *
     * @return mixed
     *
     * @version 20160504
     */
    public function getCustomerEmail() {
        Mage::helper('chargepayment')->getCustomerEmail();
    }
}