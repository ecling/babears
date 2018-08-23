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
class Adyen_Payment_Model_Adyen_Pos extends Adyen_Payment_Model_Adyen_Abstract {

	protected $_canUseInternal = false;
	protected $_code = 'adyen_pos';
    protected $_formBlockType = 'adyen/form_pos';
    protected $_infoBlockType = 'adyen/info_pos';
    protected $_paymentMethod = 'pos';
    protected $_isInitializeNeeded = true;

    /**
     * @var GUEST_ID , used when order is placed by guests
     */
    const GUEST_ID = 'customer_';

    protected $_paymentMethodType = 'pos';

    public function getPaymentMethodType() {
        return $this->_paymentMethodType;
    }

    /*
     * Check if IP filter is active
     */
    public function isAvailable($quote = null)
    {
        $isAvailable = parent::isAvailable($quote);
        // check if ip range is enabled
        $ipFilter = $this->_getConfigData('ip_filter', 'adyen_pos');

        if($isAvailable && $ipFilter != 0) {
            $ip =  Mage::helper('adyen')->getClientIp();
            switch ($ipFilter) {
                case '1':
                    // check if ip in in list
                    $_list = $this->_getConfigData('ip_filter_ips', 'adyen_pos');
                    $_list = str_replace(' ', '', $_list);
                    $_list = explode(',', $_list);
                    $isAvailable = in_array($ip, $_list);
                    break;
                case '2':
                default:
                    // check if ip is in range
                    $from =  $this->_getConfigData('ip_filter_from', 'adyen_pos');
                    $to =  $this->_getConfigData('ip_filter_to', 'adyen_pos');
                    $isAvailable = Mage::helper('adyen')->ipInRange($ip, $from, $to);
                    break;
            }
        }
        return $isAvailable;
    }

    /**
     * @desc Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function assignData($data) {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();

        // save value remember details checkbox
        $info->setAdditionalInformation('store_cc', $data->getStoreCc());

        return $this;
    }

    /**
     * @desc Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('adyen/process/redirect');
    }

    /**
     * @desc prepare params array to send it to gateway page via POST
     * @return array
     */
    public function getFormFields() {
        $this->_initOrder();
        $order = $this->_order;
        $realOrderId = $order->getRealOrderId();
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        $amount = Mage::helper('adyen')->formatAmount($order->getGrandTotal(),$orderCurrencyCode);
        $customerId = $order->getCustomerId();
        $customerEmail = $order->getCustomerEmail();

        $adyFields = array();
        $adyFields['currencyCode'] = $orderCurrencyCode;
        $adyFields['paymentAmount'] = $amount;
        $adyFields['merchantReference'] = $realOrderId;
        $adyFields['paymentAmountGrandTotal'] = $order->formatPrice($order->getGrandTotal()); // for showing only

        // for recurring payments
        $recurringType = $this->_getConfigData('recurringtypes', 'adyen_pos');

        if($order->getPayment()->getAdditionalInformation("store_cc") != "") {
            $adyFields['recurringContract'] = $recurringType;
        }

        $adyFields['shopperReference'] = (!empty($customerId)) ? $customerId : self::GUEST_ID . $realOrderId;
        $adyFields['shopperEmail'] = $customerEmail;

        Mage::log($adyFields, self::DEBUG_LEVEL, 'adyen_http-request.log',true);

        return $adyFields;
    }

    public function getFormName() {
		return "Adyen POS";
    }

    /**
     * Return redirect block type
     *
     * @return string
     */
    public function getRedirectBlockType() {
        return $this->_redirectBlockType;
    }

    public function initialize($paymentAction, $stateObject) {
        $state = Mage_Sales_Model_Order::STATE_NEW;
        $stateObject->setState($state);
        $stateObject->setStatus($this->_getConfigData('order_status'));
    }

    public function showRememberThisCheckoutbox() {
        $recurringType = $this->_getConfigData('recurringtypes', 'adyen_pos');
        if($recurringType == "ONECLICK" || $recurringType == "ONECLICK,RECURRING") {
            return true;
        }
        return false;
    }

}
