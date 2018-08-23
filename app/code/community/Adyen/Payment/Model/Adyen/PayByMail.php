<?php

/**
 * Adyen Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category	Adyen
 * @package	Adyen_Payment
 * @copyright	Copyright (c) 2011 Adyen (http://www.adyen.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */
class Adyen_Payment_Model_Adyen_PayByMail extends Adyen_Payment_Model_Adyen_Abstract {

    const METHODCODE = 'adyen_pay_by_mail';
    protected $_code = self::METHODCODE;
    protected $_formBlockType = 'adyen/form_payByMail';
    protected $_infoBlockType = 'adyen/info_payByMail';
    protected $_paymentMethod = 'pay_by_mail';
    protected $_canUseCheckout = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = true;
    protected $_isInitializeNeeded = true;

    protected $_paymentMethodType = 'hpp';

    public function getPaymentMethodType() {
        return $this->_paymentMethodType;
    }

    /**
     * @var GUEST_ID , used when order is placed by guests
     */
    const GUEST_ID = 'customer_';

    public function __construct()
    {
        // check if this is adyen_cc payment method because this function is as well used for oneclick payments
        if($this->getCode() == "adyen_pay_by_mail") {
            $visible = Mage::getStoreConfig("payment/adyen_pay_by_mail/visible_type");
            if($visible == "backend") {
                $this->_canUseCheckout = false;
                $this->_canUseInternal = true;
            } else if($visible == "frontend") {
                $this->_canUseCheckout = true;
                $this->_canUseInternal = false;
            } else {
                $this->_canUseCheckout = true;
                $this->_canUseInternal = true;
            }
        }
        parent::__construct();
    }

    public function assignData($data)
    {

    }


    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus($this->_getConfigData('order_status'));


        $payment = $this->getInfoInstance();
        $payment->setLastTransId($this->getTransactionId())->setIsTransactionPending(true);

        // create payment link and add it to comment history and send to shopper
        $fields = $this->getFormFields();

        $url = $this->getFormUrl($fields);

        $payment->setAdditionalInformation('payment_url', $url);
    }

    /**
     * @param array $fields
     * @return string
     */
    public function getFormUrl($fields = array())
    {
        $isConfigDemoMode = $this->getConfigDataDemoMode();

        return Mage::helper('adyen/payment')->prepareFieldsforUrl($fields, $isConfigDemoMode);
    }

    /**
     * @return mixed
     */
    public function getFormFields()
    {
        $this->_initOrder();
        /* @var $order Mage_Sales_Model_Order */
        $order             = $this->_order;
        $incrementId       = $order->getIncrementId();
        $realOrderId       = $order->getRealOrderId();
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        $shopperIP         = trim($order->getRemoteIp());

        $billingCountryCode = (is_object($order->getBillingAddress()) && $order->getBillingAddress()->getCountry() != "") ?
            $order->getBillingAddress()->getCountry() :
            false ;

        $hasDeliveryAddress = $order->getShippingAddress()!= null;


        $adyFields = Mage::helper('adyen/payment')->prepareFields(
            $orderCurrencyCode,
            $incrementId,
            $realOrderId,
            $order->getGrandTotal(),
            $order->getCustomerEmail(),
            $order->getCustomerId(),
            array(),
            $order->getStoreId(),
            Mage::getStoreConfig('general/locale/code', $order->getStoreId()),
            $billingCountryCode,
            $shopperIP,
            $this->getInfoInstance()->getCcType(),
            $this->getInfoInstance()->getMethod(),

            trim($this->getInfoInstance()->getPoNumber()),
            $this->_code,
            $hasDeliveryAddress,
            $order
        );


        // calculate the signature
        $secretWord = Mage::helper('adyen/payment')->_getSecretWord($order->getStoreId(), $this->_code);
        $adyFields['merchantSig'] = Mage::helper('adyen/payment')->createHmacSignature($adyFields, $secretWord);

        Mage::log($adyFields, self::DEBUG_LEVEL, 'adyen_http-request.log', true);

        return $adyFields;
    }

    /**
     * @return bool
     */
    public function isBillingAgreement()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canCreateAdyenSubscription()
    {
        // validate if recurringType is correctly configured
        $recurringType = $this->_getConfigData('recurringtypes', 'adyen_abstract');
        if($recurringType == "RECURRING" || $recurringType == "ONECLICK,RECURRING") {
            return true;
        }
        return false;
    }

    /**
     * @param Adyen_Payment_Model_Billing_Agreement $billingAgreement
     * @param Mage_Sales_Model_Quote_Payment        $paymentInfo
     *
     * @return $this
     */
    public function initBillingAgreementPaymentInfo(
        Adyen_Payment_Model_Billing_Agreement $billingAgreement,
        Mage_Sales_Model_Quote_Payment $paymentInfo)
    {
        // do nothing for now
        return $this;
    }
}