<?php
/**
 * Block for Hosted Redirect
 *
 * Class CheckoutApi_ChargePayment_Block_Hosted_Redirect
 */
class CheckoutApi_ChargePayment_Block_Hosted_Redirect  extends Mage_Core_Block_Template
{
    /**
     * @var Session chargepayment/session_quote
     */
    protected $_session;

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_session = Mage::getSingleton('chargepayment/session_quote');

        parent::_construct();
    }

    /**
     * Return params for form
     *
     * @return mixed
     */
    public function getPostParams() { 
        return $this->_session->getHostedPaymentParams();
    }

    /**
     * Return form action url
     *
     * @return string
     */
    public function getPostUrl() {
        $mode       =  Mage::getModel('chargepayment/hosted')->getEndpointMode();
        $hostedUrl  = $mode === CheckoutApi_ChargePayment_Helper_Data::API_MODE_LIVE
            ? CheckoutApi_ChargePayment_Helper_Data::REDIRECT_PAYMENT_URL_LIVE
            : CheckoutApi_ChargePayment_Helper_Data::REDIRECT_PAYMENT_URL;

        return $hostedUrl;
    }
}