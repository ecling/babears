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
class Adyen_Payment_Model_Adyen_Openinvoice extends Adyen_Payment_Model_Adyen_Hpp {

    const METHODCODE = 'adyen_openinvoice';
    protected $_canUseInternal = false;
    protected $_code = self::METHODCODE;
    protected $_formBlockType = 'adyen/form_openinvoice';
    protected $_infoBlockType = 'adyen/info_openinvoice';
    protected $_paymentMethod = 'openinvoice';


    public function isApplicableToQuote($quote, $checksBitMask)
    {

        if($this->_getConfigData('failed_attempt_disable', 'adyen_openinvoice')) {
            $openInvoiceInactiveForThisQuoteId = Mage::getSingleton('checkout/session')->getOpenInvoiceInactiveForThisQuoteId();
            if($openInvoiceInactiveForThisQuoteId != "") {
                // check if quoteId is the same
                if($quote->getId() == $openInvoiceInactiveForThisQuoteId) {
                    return false;
                }
            }
        }

        // different don't show
        if($this->_getConfigData('different_address_disable', 'adyen_openinvoice')) {

            // get billing and shipping information
            $billing = $quote->getBillingAddress()->getData();
            $shipping = $quote->getShippingAddress()->getData();

            // check if the following items are different: street, city, postcode, region, countryid
            if(isset($billing['street']) && isset($billing['city']) && $billing['postcode'] && isset($billing['region']) && isset($billing['country_id'])) {
                $billingAddress = array($billing['street'], $billing['city'], $billing['postcode'], $billing['region'],$billing['country_id']);
            } else {
                $billingAddress = array();
            }
            if(isset($shipping['street']) && isset($shipping['city']) && $shipping['postcode'] && isset($shipping['region']) && isset($shipping['country_id'])) {
                $shippingAddress = array($shipping['street'], $shipping['city'], $shipping['postcode'], $shipping['region'],$shipping['country_id']);
            } else {
                $shippingAddress = array();
            }

            // if the result are not the same don't show the payment method open invoice
            $diff = array_diff($billingAddress,$shippingAddress);
            if(is_array($diff) && !empty($diff)) {
                return false;
            }
        }
        return parent::isApplicableToQuote($quote, $checksBitMask);
    }

    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();
        $info->setCcType('openinvoice');

        // check if option gender or date of birth is enabled
        $genderShow = $this->genderShow();
        $dobShow = $this->dobShow();
        $telephoneShow = $this->telephoneShow();

        if($genderShow || $dobShow || $telephoneShow) {

            // set gender and dob to the quote
            $quote = $this->getQuote();

            // dob must be in yyyy-MM-dd
            $dob = $data->getYear() . "-" . $data->getMonth() . "-" . $data->getDay();

            if($dobShow)
                $quote->setCustomerDob($dob);

            if($genderShow) {
                $quote->setCustomerGender($data->getGender());
                // Fix for OneStepCheckout (won't convert quote customerGender to order object)
                $info->setAdditionalInformation('customerGender', $data->getGender());
            }

            if($telephoneShow) {
                $telephone = $data->getTelephone();
                $quote->getBillingAddress()->setTelephone($data->getTelephone());
            }

            /* Check if the customer is logged in or not */
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {

                /* Get the customer data */
                $customer = Mage::getSingleton('customer/session')->getCustomer();

                // set the email and/or gender
                if($dobShow) {
                    $customer->setDob($dob);
                }

                if($genderShow) {
                    $customer->setGender($data->getGender());
                }

                if($telephoneShow) {
                    $billingAddress = $customer->getPrimaryBillingAddress();
                    if($billingAddress) {
                        $billingAddress->setTelephone($data->getTelephone());
                    }
                }

                // save changes into customer
                $customer->save();
            }
        }

        $dfValue = $data->getDfvalue();
        if ($dfValue != "") {
            $info->setAdditionalInformation('dfvalue', $dfValue);
        }

        return $this;
    }

    /**
     * @desc Get url of Adyen payment
     * @return string
     */
    public function getFormUrl()
    {
        $paymentRoutine = $this->_getConfigData('payment_routines', 'adyen_hpp');
        $openinvoiceType = $this->_getConfigData('openinvoicetypes', 'adyen_openinvoice');

        switch ($this->getConfigDataDemoMode()) {
            case true:
                if ($paymentRoutine == 'single' && empty($openinvoiceType)) {
                    $url = 'https://test.adyen.com/hpp/pay.shtml';
                } else {
                    $url = 'https://test.adyen.com/hpp/skipDetails.shtml';
                }
                break;
            default:
                if ($paymentRoutine == 'single' && empty($openinvoiceType)) {
                    $url = 'https://live.adyen.com/hpp/pay.shtml';
                } else {
                    $url = 'https://live.adyen.com/hpp/skipDetails.shtml';
                }
                break;
        }
        return $url;
    }
    
    protected function _loadProductById($id)
    {
        return Mage::getModel('catalog/product')->load($id);
    }

    protected function getGenderText($genderId)
    {
        $result = "";
        if($genderId == '1') {
            $result = 'MALE';
        } elseif($genderId == '2') {
            $result = 'FEMALE';
        }
        return $result;
    }

    /**
     * Date Manipulation
     * @param type $date
     * @param type $format
     * @return type date
     */
    public function getDate($date = null, $format = 'Y-m-d H:i:s') {
        if (strlen($date) < 0) {
            $date = date('d-m-Y H:i:s');
        }
        $timeStamp = new DateTime($date);
        return $timeStamp->format($format);
    }


    public function genderShow()
    {
        return $this->_getConfigData('gender_show', 'adyen_openinvoice');
    }

    public function dobShow()
    {
        return $this->_getConfigData('dob_show', 'adyen_openinvoice');
    }

    public function telephoneShow()
    {
        return $this->_getConfigData('telephone_show', 'adyen_openinvoice');
    }

    public function isRatePay()
    {
        if ($this->_getConfigData('openinvoicetypes', Adyen_Payment_Model_Adyen_Openinvoice::METHODCODE) == Adyen_Payment_Helper_Data::RATEPAY) {
            return true;
        }
        return false;
    }

    public function getRatePayId()
    {
        return $this->_getConfigData('ratepay_id', Adyen_Payment_Model_Adyen_Openinvoice::METHODCODE);
    }

}
