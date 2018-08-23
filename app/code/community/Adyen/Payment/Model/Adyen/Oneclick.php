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
class Adyen_Payment_Model_Adyen_Oneclick extends Adyen_Payment_Model_Adyen_Cc {

    protected $_code = 'adyen_oneclick';
    protected $_formBlockType = 'adyen/form_oneclick';
    protected $_infoBlockType = 'adyen/info_oneclick';
    protected $_paymentMethod = 'oneclick';
    protected $_canUseInternal = true; // not possible through backoffice interface
    protected $_customerInteraction;
    protected $_canUseForMultishipping = true;


    public function isAvailable($quote=null) {
        $isAvailble = parent::isAvailable($quote);

        // extra check if contract_type is allowed
        if($isAvailble) {
            $recurringPaymentType = $this->getRecurringPaymentType();
            $recurringDetails = $this->getRecurringDetails();

            if(isset($recurringDetails['recurring_type'])) {

                $result = strpos($recurringDetails['recurring_type'], $recurringPaymentType);

                if($result !== false) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        return $isAvailble;
    }

    /**
     * Ability to set the code, for dynamic payment methods.
     * @param $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->_code = $code;
        return $this;
    }

    public function assignData($data) {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();

        // get storeId
        $session = Mage::helper('adyen')->getSession();
        
        if(Mage::app()->getStore()->isAdmin()) {
            $store = $session->getStore();
        } else {
            $store = Mage::app()->getStore();
        }
        $storeId = $store->getId();

        if($data->getRecurringDetailReference()) {
            // this can be the case if you select the recurring card from the POS express checkout mechanisme
            $info->setAdditionalInformation('recurring_detail_reference', $data->getRecurringDetailReference());
        } else {
            // Get recurringDetailReference from config
            $recurringDetailReference = str_replace('adyen_oneclick_', '', $info->getData('method'));
            $info->setAdditionalInformation('recurring_detail_reference', $recurringDetailReference);
        }

        $ccType = Mage::getStoreConfig("payment/".$this->getCode() . "/variant", $storeId);
        $ccType = Mage::helper('adyen')->getMagentoCreditCartType($ccType);
        $info->setCcType($ccType);

        if ($this->isCseEnabled()) {
            $method = $this->getCode();
            $encryptedData = $data->getData('encrypted_data_'.$method);
            $session->setData('encrypted_data_'.$method, $encryptedData);
        } else {

            // check if expiry month and year is changed
            $expiryMonth = $data->getOneclickExpMonth();
            $expiryYear = $data->getOneclickExpYear();
            $cvcCode = $data->getOneclickCid();

            $cardHolderName = Mage::getStoreConfig("payment/".$this->getCode() . "/card_holderName", $storeId);
            $last4Digits = Mage::getStoreConfig("payment/".$this->getCode() . "/card_number", $storeId);
            $cardHolderName = Mage::getStoreConfig("payment/".$this->getCode() . "/card_holderName", $storeId);

            // just set default data for info block only
            $info->setCcType($ccType)
                ->setCcOwner($cardHolderName)
                ->setCcLast4($last4Digits)
                ->setCcExpMonth($expiryMonth)
                ->setCcExpYear($expiryYear)
                ->setCcCid($cvcCode);
        }

        if(Mage::helper('adyen/installments')->isInstallmentsEnabled()) {
            $info->setAdditionalInformation('number_of_installments', $data->getInstallment());
        } else {
            $info->setAdditionalInformation('number_of_installments', "");

        }

        if($info->getAdditionalInformation('number_of_installments') != "") {
            // recalculate the totals so that extra fee is defined
            $quote = (Mage::getModel('checkout/type_onepage') !== false)? Mage::getModel('checkout/type_onepage')->getQuote(): Mage::getModel('checkout/session')->getQuote();
            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
        }

        // get and set customer interaction needed for payment request
        $this->hasCustomerInteraction();
        $info->setAdditionalInformation('customer_interaction', $this->_customerInteraction);

        return $this;
    }

    /**
     * @desc CustomerInteraction is set by the recurring_payment_type or controlled by Adyen_Subscription module
     * @param $customerInteraction
     */
    public function setCustomerInteraction($customerInteraction)
    {
        $this->_customerInteraction = (bool) $customerInteraction;
    }

    public function hasCustomerInteraction()
    {
        if($this->_customerInteraction === null) {
            $recurringPaymentType = $this->getRecurringPaymentType();
            if($recurringPaymentType == "ONECLICK") {
                $this->_customerInteraction = true;
            } else {
                $this->_customerInteraction = false;
            }
        }
        return $this->_customerInteraction;

    }

    public function getRecurringPaymentType()
    {
        // For admin always use Recurring
        if(Mage::app()->getStore()->isAdmin()) {
            return "RECURRING";
        } else {
            return $this->_getConfigData('recurring_payment_type', 'adyen_oneclick');
        }
    }


    /**
     * @return Adyen_Payment_Model_Billing_Agreement
     */
    public function getBillingAgreement()
    {
        $subscriptionReference = str_replace('adyen_oneclick_', '', $this->getCode());

        return Mage::getModel('adyen/billing_agreement')->getCollection()
            ->addFieldToFilter('reference_id', $subscriptionReference)
            ->getFirstItem();
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
        try {
            $recurringDetailReference = $billingAgreement->getReferenceId();
            $paymentInfo->setMethod('adyen_oneclick_'.$recurringDetailReference);
            $paymentInfo->setAdditionalInformation('recurring_detail_reference', $recurringDetailReference);

            // set the ccType needed for Sepa, Sofort and Ideal
            $agreementData = $billingAgreement->getAgreementData();
            if(isset($agreementData['variant'])) {
                $paymentInfo->setCcType($agreementData['variant']);
            }
        } catch(Exception $e) {
            Adyen_Payment_Exception::logException($e);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isBillingAgreement()
    {
        return true;
    }

    public function canCreateAdyenSubscription() {

        // get storeId
        if(Mage::app()->getStore()->isAdmin()) {
            $store = Mage::getSingleton('adminhtml/session_quote')->getStore();
        } else {
            $store = Mage::app()->getStore();
        }
        $storeId = $store->getId();

        // Only cards that are saved as RECURRING or ONECLICK,RECURRING can be used for subscription
        $recurringType = $this->getConfigData('recurring_type', $storeId);
        if($recurringType == "RECURRING" || $recurringType == "ONECLICK,RECURRING") {
            return true;
        }
        return false;
    }

    /**
     * @param Adyen_Payment_Model_Billing_Agreement $billingAgreement
     * @param array                                 $data
     *
     * @return $this
     */
    public function parseRecurringContractData(
        Adyen_Payment_Model_Billing_Agreement $billingAgreement,
        array $data)
    {
        $billingAgreement
            ->setMethodCode($this->getCode())
            ->setReferenceId($data['recurringDetailReference'])
            ->setCreatedAt($data['creationDate']);

        $creationDate =  str_replace(' ', '-', $data['creationDate']);
        $billingAgreement->setCreatedAt($creationDate);

        //Billing agreement SEPA
        if (isset($data['bank_iban'])) {
            $billingAgreement->setAgreementLabel(Mage::helper('adyen')->__('%s, %s',
                $data['bank_iban'],
                $data['bank_ownerName']
            ));
        }

        // Billing agreement is CC
        if (isset($data['card_number'])) {
            $ccType = $data['variant'];
            $ccTypes = Mage::helper('adyen')->getCcTypesAltData();

            if (isset($ccTypes[$ccType])) {
                $ccType = $ccTypes[$ccType]['name'];
            }

            $label = Mage::helper('adyen')->__('%s, %s, **** %s',
                $ccType,
                $data['card_holderName'],
                $data['card_number'],
                $data['card_expiryMonth'],
                $data['card_expiryYear']
            );
            $billingAgreement->setAgreementLabel($label);
        }

        if (isset($data['variant']) && $data['variant'] == 'paypal') {
            
            $email = "";
            if (isset($data['tokenDetails']['tokenData']['EmailId'])) {
                $email = $data['tokenDetails']['tokenData']['EmailId'];
            } elseif (isset($data['lastKnownShopperEmail'])) {
                $email = $data['lastKnownShopperEmail'];
            }
            $label = Mage::helper('adyen')->__('PayPal %s',
                $email
            );

            $billingAgreement->setAgreementLabel($label);
        }

        return $this;
    }

    public function getRecurringDetails() {

        if(Mage::app()->getStore()->isAdmin()) {
            $storeId = Mage::getSingleton('adminhtml/session_quote')->getStoreId();
        } else {
            $storeId = Mage::app()->getStore()->getStoreId();
        }

        $recurringDetails = Mage::getStoreConfig("payment/".$this->getCode(), $storeId);
        return $recurringDetails;
    }
}
