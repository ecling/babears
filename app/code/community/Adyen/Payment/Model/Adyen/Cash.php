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
class Adyen_Payment_Model_Adyen_Cash extends Adyen_Payment_Model_Adyen_Abstract {

    protected $_code = 'adyen_cash';
    protected $_formBlockType = 'adyen/form_cash';
    protected $_infoBlockType = 'adyen/info_cash';
    protected $_paymentMethod = 'cash';
    protected $_canUseCheckout = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = true;


    public function __construct()
    {
        // check if this is adyen_cc payment method because this function is as well used for oneclick payments
        if($this->getCode() == "adyen_cash") {
            $visible = Mage::getStoreConfig("payment/adyen_cash/visible_type");
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

    /*
     * Check if IP filter is active
     */
    public function isAvailable($quote = null)
    {
        $isAvailable = parent::isAvailable($quote);
        // check if ip range is enabled
        $ipFilter = $this->_getConfigData('ip_filter', 'adyen_cash');

        if($isAvailable && $ipFilter) {
            // check if ip is in range
            $ip =  Mage::helper('adyen')->getClientIp();
            $from =  $this->_getConfigData('ip_filter_from', 'adyen_cash');
            $to =  $this->_getConfigData('ip_filter_to', 'adyen_cash');
            $isAvailable = Mage::helper('adyen')->ipInRange($ip, $from, $to);
        }
        return $isAvailable;
    }

    public function assignData($data)
    {

    }


    public function authorize(Varien_Object $payment, $amount) {


        $payment->setLastTransId($this->getTransactionId())->setIsTransactionPending(true);

        $order = $payment->getOrder();

        /*
         * Do not send a email notification when order is created.
         * Only do this on the AUHTORISATION notification.
         * This is needed for old versions where there is no check if email is already send
         */
        $order->setCanSendNewEmailFlag(false);

        if (Mage::app()->getStore()->isAdmin()) {
            $storeId = $payment->getOrder()->getStoreId();
        } else {
            $storeId = null;
        }

        $merchantAccount = trim($this->_getConfigData('merchantAccount', 'adyen_abstract', $storeId));
        $incrementId = $order->getIncrementId();
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        $customerId = $order->getCustomerId();
        $amount = Mage::helper('adyen')->formatAmount($amount, $orderCurrencyCode);

        // do cash API
        $request = array(
            "merchantAccount" => $merchantAccount,
            "shopperReference" => $customerId,
            "variantCode" => 'c_cash',
            "reference" => $incrementId,
            "amount.value" => $amount,
            "amount.currency" => $orderCurrencyCode
        );

        $ch = curl_init();

        $isConfigDemoMode = $this->getConfigDataDemoMode($storeId = null);
        $wsUsername = $this->getConfigDataWsUserName($storeId);
        $wsPassword = $this->getConfigDataWsPassword($storeId);

        if ($isConfigDemoMode)
        {
            curl_setopt($ch, CURLOPT_URL, "https://pal-test.adyen.com/pal/servlet/CustomPayment/beginCustomPayment");
        }
        else
        {
            curl_setopt($ch, CURLOPT_URL, "https://pal-live.adyen.com/pal/servlet/CustomPayment/beginCustomPayment");
        }

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC  );
        curl_setopt($ch, CURLOPT_USERPWD,$wsUsername.":".$wsPassword);
        curl_setopt($ch, CURLOPT_POST,count($request));
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($request));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $results = curl_exec($ch);

        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpStatus != 200) {
            Mage::throwException(
                Mage::helper('adyen')->__('HTTP Status code %s received, data %s', $httpStatus, $results)
            );
        }

        if ($results === false) {
            Mage::throwException(
                Mage::helper('adyen')->__('Got an empty response, status code %s', $httpStatus)
            );
        }
    }
}