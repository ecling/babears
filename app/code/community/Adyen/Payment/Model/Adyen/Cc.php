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
class Adyen_Payment_Model_Adyen_Cc extends Adyen_Payment_Model_Adyen_Abstract
    implements Mage_Payment_Model_Billing_Agreement_MethodInterface {

    protected $_code = 'adyen_cc';
    protected $_formBlockType = 'adyen/form_cc';
    protected $_infoBlockType = 'adyen/info_cc';
    protected $_paymentMethod = 'cc';
    protected $_canCreateBillingAgreement = true;
    protected $_ccTypes;
    protected $_canUseForMultishipping = true;

    /**
     * 1) Called everytime the adyen_cc is called or used in checkout
     * @description Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data) {

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();

        // set number of installements
        $info->setAdditionalInformation('number_of_installments', $data->getAdditionalData());

        // save value remember details checkbox
        $info->setAdditionalInformation('store_cc', $data->getStoreCc());

        if ($this->isCseEnabled()) {
            $info->setCcType($data->getCcType());

            if($data->getEncryptedData() == "false" || $data->getEncryptedData() == "") {
                Mage::throwException(Mage::helper('adyen')->__('Invalid credit number card.'));
            } else if($data->getEncryptedData()) {
                $session = Mage::helper('adyen')->getSession();
                $method = $this->getCode();
                $session->setData('encrypted_data_'.$method, $data->getEncryptedData());
            }
        } else {
            $info->setCcType($data->getCcType())
                ->setCcOwner($data->getCcOwner())
                ->setCcLast4(substr($data->getCcNumber(), -4))
                ->setCcNumber($data->getCcNumber())
                ->setCcExpMonth($data->getCcExpMonth())
                ->setCcExpYear($data->getCcExpYear())
                ->setCcCid($data->getCcCid())
                ->setPoNumber($data->getAdditionalData());
        }

        if($info->getAdditionalInformation('number_of_installments') != "") {
            // recalculate the totals so that extra fee is defined
            $quote = (Mage::getModel('checkout/type_onepage') !== false)? Mage::getModel('checkout/type_onepage')->getQuote(): Mage::getModel('checkout/session')->getQuote();
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
        }

        return $this;
    }

    public function validate()
    {
        parent::validate();
    }

    public function getPossibleInstallments() {
        // retrieving quote
        $quote = (Mage::getModel('checkout/type_onepage') !== false)? Mage::getModel('checkout/type_onepage')->getQuote(): Mage::getModel('checkout/session')->getQuote();

        // get selected payment method for now
        $payment = $quote->getPayment();

        $ccType = null;
        if($payment && !empty($payment)) {
            if($payment->getMethod()) {
                $info = $payment->getMethodInstance();

                $instance = $info->getInfoInstance();
                $ccType = $instance->getCcType();
            }
        }

        $result = Mage::helper('adyen/installments')->getInstallmentForCreditCardType($ccType);

        return $result;
    }

    /**
     * @desc Helper functions to get config data
     */
    public function isCseEnabled()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
            $storeId = $quote->getStoreId();
            return Mage::getStoreConfig("payment/adyen_cc/cse_enabled", $storeId);
        }
        return Mage::getStoreConfig("payment/adyen_cc/cse_enabled");
    }

    /**
     * @return string
     */
    public function getCsePublicKey()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
            $storeId = $quote->getStoreId();
        } else {
            $storeId = null;
        }

        if (Mage::helper('adyen')->getConfigDataDemoMode($storeId)) {
            return trim(Mage::getStoreConfig("payment/adyen_cc/cse_publickey_test", $storeId));
        }
        return trim(Mage::getStoreConfig("payment/adyen_cc/cse_publickey",$storeId));
    }

    /**
     * @desc Specific functions for 3d secure validation
     */

    public function getOrderPlaceRedirectUrl() {
        $redirectUrl = Mage::getSingleton('customer/session')->getRedirectUrl();

        if (!empty($redirectUrl)) {
            Mage::getSingleton('customer/session')->unsRedirectUrl();
            return Mage::getUrl($redirectUrl);
        }
        else {
            return parent::getOrderPlaceRedirectUrl();
        }
    }

    /**
     * This method is called for redirect to 3D secure
     *
     * @return mixed
     */
    public function getFormUrl() {
        $this->_initOrder();
        $order = $this->_order;
        $payment = $order->getPayment();
        return $payment->getAdditionalInformation('issuerUrl');
    }

    public function getFormName() {
        return "Adyen CC";
    }

    public function getFormFields() {
        $this->_initOrder();
        $order = $this->_order;
        $payment = $order->getPayment();

        $adyFields = array();
        $adyFields['PaReq'] = $payment->getAdditionalInformation('paRequest');
        $adyFields['MD'] = $payment->getAdditionalInformation('md');
        $adyFields['TermUrl'] = Mage::getUrl('adyen/process/validate3d');

        return $adyFields;
    }

    /**
     * @desc setAvailableCCypes to remove MAESTRO as creditcard type for the Adyen_Subscription module
     * @param $ccTypes
     */
    public function setAvailableCCypes($ccTypes) {
        $this->_ccTypes = $ccTypes;
    }

    /**
     * @return mixed
     */
    public function getAvailableCCTypes() {
        if(!$this->_ccTypes) {
            $types = Mage::helper('adyen')->getCcTypes();
            $availableTypes = $this->_getConfigData('cctypes', 'adyen_cc');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code => $name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
            $this->_ccTypes = $types;
        }
        return $this->_ccTypes;
    }

    public function canCreateAdyenSubscription() {

        // validate if recurringType is correctly configured
        $recurringType = $this->_getConfigData('recurringtypes', 'adyen_abstract');
        if($recurringType == "RECURRING" || $recurringType == "ONECLICK,RECURRING") {
            return true;
        }
        return false;
    }

    /**
     * @param Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $isAvailable = parent::isAvailable($quote);

        if (!is_null($quote)) {
            $disableZeroTotal = Mage::getStoreConfig('payment/adyen_cc/disable_zero_total', $quote->getStoreId());
        } else {
            $disableZeroTotal = Mage::getStoreConfig('payment/adyen_cc/disable_zero_total');
        }
        
        if (!is_null($quote) && $quote->getGrandTotal() <= 0 && $disableZeroTotal) {
            return false;
        }

        return $isAvailable;
    }
}
