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
class Adyen_Payment_Model_Adyen_Data_PaymentRequest extends Adyen_Payment_Model_Adyen_Data_Abstract {

    public $additionalAmount;
    public $amount;
    public $bankAccount;
    public $browserInfo;
    public $card;
    public $dccQuote;
    public $deliveryAddress;
    public $billingAddress;
    public $fraudOffset;
    public $merchantAccount;
    public $mpiData;
    public $orderReference;
    public $recurring;
    public $selectedBrand;
    public $selectedRecurringDetailReference;
    public $shopperEmail;
    public $shopperIP;
    public $shopperInteraction;
    public $shopperReference;
    public $shopperStatement;
    public $additionalData;

	// added for boleto
	public $shopperName;
	public $socialSecurityNumber;
    const GUEST_ID = 'customer_';

    public function __construct() {
    	$this->browserInfo = new Adyen_Payment_Model_Adyen_Data_BrowserInfo();
        $this->card = new Adyen_Payment_Model_Adyen_Data_Card();
        $this->amount = new Adyen_Payment_Model_Adyen_Data_Amount();
        $this->additionalData = new Adyen_Payment_Model_Adyen_Data_AdditionalData();
        $this->shopperName = new Adyen_Payment_Model_Adyen_Data_ShopperName(); // for boleto
        $this->bankAccount = new Adyen_Payment_Model_Adyen_Data_BankAccount(); // for SEPA
    }

    public function create(
        Varien_Object $payment,
        $amount,
        $paymentMethod = null,
        $merchantAccount = null,
        $recurringType = null,
        $recurringPaymentType = null,
        $enableMoto = null
    ) {
        $order = $payment->getOrder();
        $incrementId = $order->getIncrementId();
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        // override amount because this amount uses the right currency

        $realOrderId = $order->getRealOrderId();
		
		$customerId = Mage::helper('adyen/payment')->getShopperReference($order->getCustomerId(), $realOrderId);

        $this->reference = $incrementId;
        $this->merchantAccount = $merchantAccount;
        $this->amount->currency = $orderCurrencyCode;
        $this->amount->value = Mage::helper('adyen')->formatAmount($amount, $orderCurrencyCode);

        //shopper data
        $customerEmail = $order->getCustomerEmail();
        $this->shopperEmail = $customerEmail;
        $this->shopperIP = $order->getRemoteIp();
        $this->shopperReference = (!empty($customerId)) ? $customerId : self::GUEST_ID . $realOrderId;

        // Set the recurring contract for apple pay do not save as oneclick or recurring because that will give errors on recurring payments
        if($paymentMethod != "apple_pay" && $recurringType) {
            if($paymentMethod == "oneclick") {
                // For ONECLICK look at the recurringPaymentType that the merchant has selected in Adyen ONECLICK settings
                if($payment->getAdditionalInformation('customer_interaction')) {
                    $this->recurring = new Adyen_Payment_Model_Adyen_Data_Recurring();
                    $this->recurring->contract = "ONECLICK";
                } else {
                    $this->recurring = new Adyen_Payment_Model_Adyen_Data_Recurring();
                    $this->recurring->contract = "RECURRING";
                }
            } elseif($paymentMethod == "cc") {
                // if save card is disabled only shoot in as recurring if recurringType is set to ONECLICK,RECURRING
                if($payment->getAdditionalInformation("store_cc") == "" && $recurringType == "ONECLICK,RECURRING") {
                    $this->recurring = new Adyen_Payment_Model_Adyen_Data_Recurring();
                    $this->recurring->contract = "RECURRING";
                } elseif($payment->getAdditionalInformation("store_cc") == "1") {
                    $this->recurring = new Adyen_Payment_Model_Adyen_Data_Recurring();
                    $this->recurring->contract = $recurringType;
                } elseif($recurringType == "RECURRING") {
                    // recurring permission is not needed from shopper so just save it as recurring
                    $this->recurring = new Adyen_Payment_Model_Adyen_Data_Recurring();
                    $this->recurring->contract = "RECURRING";
                }
            } else {
                $this->recurring = new Adyen_Payment_Model_Adyen_Data_Recurring();
                $this->recurring->contract = $recurringType;
            }
        }

        /**
         * Browser info
         * @var unknown_type
         */
        if(isset($_SERVER['HTTP_ACCEPT'])) {
            $this->browserInfo->acceptHeader = $_SERVER['HTTP_ACCEPT'];
        }

        if(isset($_SERVER['HTTP_USER_AGENT'])) {
            $this->browserInfo->userAgent = $_SERVER['HTTP_USER_AGENT'];
        }

        switch ($paymentMethod) {
            case "apple_pay":
            case "cc":
            case "oneclick":
            case "multibanco":
                
                $this->bankAccount = null;

                $billingAddress = $order->getBillingAddress();
                $helper = Mage::helper('adyen');

                if($billingAddress)
                {
                    // add shopperName with firstName, middleName and lastName to support PapPal seller protection
                    $this->shopperName->firstName = trim($billingAddress->getFirstname());
                    $middleName = trim($billingAddress->getMiddlename());
                    if($middleName != "") {
                        $this->shopperName->infix = trim($middleName);
                    }
                    $this->shopperName->lastName = trim($billingAddress->getLastname());

                    $this->billingAddress = new Adyen_Payment_Model_Adyen_Data_BillingAddress();
                    $this->billingAddress->street = $billingAddress->getStreet(1);
                    $this->billingAddress->houseNumberOrName = '';
                    $this->billingAddress->city = $billingAddress->getCity();
                    $this->billingAddress->postalCode = $billingAddress->getPostcode();
                    $this->billingAddress->stateOrProvince = $billingAddress->getRegionCode();
                    $this->billingAddress->country = $billingAddress->getCountryId();
                }

                $deliveryAddress = $order->getShippingAddress();
                if($deliveryAddress)
                {
                    $this->deliveryAddress = new Adyen_Payment_Model_Adyen_Data_DeliveryAddress();
                    $this->deliveryAddress->street = $deliveryAddress->getStreet(1);
                    $this->deliveryAddress->houseNumberOrName = '';
                    $this->deliveryAddress->city = $deliveryAddress->getCity();
                    $this->deliveryAddress->postalCode = $deliveryAddress->getPostcode();
                    $this->deliveryAddress->stateOrProvince = $deliveryAddress->getRegionCode();
                    $this->deliveryAddress->country = $deliveryAddress->getCountryId();
                }

                if($paymentMethod == "oneclick") {
                    $recurringDetailReference = $payment->getAdditionalInformation("recurring_detail_reference");

                    if($payment->getAdditionalInformation('customer_interaction')) {
                        $this->shopperInteraction = "Ecommerce";
                    } else {
                        $this->shopperInteraction = "ContAuth";
                    }

                    // For recurring Ideal and Sofort needs to be converted to SEPA for this it is mandatory to set selectBrand to sepadirectdebit
                    if(!$payment->getAdditionalInformation('customer_interaction')) {
                        if($payment->getCcType() == "directEbanking" || $payment->getCcType() == "ideal") {
                            $this->selectedBrand = "sepadirectdebit";
                        }
                    }
                } else {
                    $recurringDetailReference = null;
                    $this->shopperInteraction = "Ecommerce";
                }

                if($paymentMethod == "cc" && Mage::app()->getStore()->isAdmin() && $enableMoto != null && $enableMoto == 1) {
                    $this->shopperInteraction = "Moto";
                }

                // if it is a sepadirectdebit set selectedBrand to sepadirectdebit
                if($payment->getCcType() == "sepadirectdebit") {
                    $this->selectedBrand = "sepadirectdebit";
                }

                if($recurringDetailReference && $recurringDetailReference != "") {
                    $this->selectedRecurringDetailReference = $recurringDetailReference;
                }

                if ($paymentMethod == "apple_pay") {
                    $token = $payment->getAdditionalInformation("token");
                    if (!$token) {
                        Mage::throwException(Mage::helper('adyen')->__('Missing token'));
                    }

                    $kv = new Adyen_Payment_Model_Adyen_Data_AdditionalDataKVPair();
                    $kv->key = new SoapVar("payment.token", XSD_STRING, "string", "http://www.w3.org/2001/XMLSchema");
                    $kv->value = new SoapVar(base64_encode($token), XSD_STRING, "string", "http://www.w3.org/2001/XMLSchema");
                    $this->additionalData->entry = $kv;
                } else if (Mage::getModel('adyen/adyen_cc')->isCseEnabled()) {

                    $this->card = null;

                    $session = Mage::helper('adyen')->getSession();
                    $info = $payment->getMethodInstance();
                    $encryptedData = $session->getData('encrypted_data_'.$info->getCode());
                    // remove it from the session
                    $session->setData('encrypted_data_'.$info->getCode(), null);

                    if($encryptedData != "" && $encryptedData != "false" ) {
                        $kv = new Adyen_Payment_Model_Adyen_Data_AdditionalDataKVPair();
                        $kv->key = new SoapVar("card.encrypted.json", XSD_STRING, "string", "http://www.w3.org/2001/XMLSchema");
                        $kv->value = new SoapVar($encryptedData, XSD_STRING, "string", "http://www.w3.org/2001/XMLSchema");
                        $this->additionalData->entry = $kv;
                    } else {
                        if($paymentMethod == 'cc') {

                            // log the browser data to see why it is failing
                            Mage::log($_SERVER['HTTP_USER_AGENT'], Zend_Log::ERR, "adyen_exception.log", true);

                            // For CC encrypted data is needed if you use CSE
                            Adyen_Payment_Exception::throwException(
                                Mage::helper('adyen')->__('Missing the encrypted data value. Make sure the Client Side Encryption(CSE) script did encrypt the Credit Card details')
                            );
                        }
                    }
				}
				else {

                    if($recurringDetailReference && $recurringDetailReference != "") {

                        // this is only needed for creditcards
                        if($payment->getCcCid() != ""  && $payment->getCcExpMonth() != "" &&  $payment->getCcExpYear() != "")
                        {
                            if($recurringType != "RECURRING") {
                                $this->card->cvc = $payment->getCcCid();
                            }

                            $this->card->expiryMonth = $payment->getCcExpMonth();
                            $this->card->expiryYear = $payment->getCcExpYear();
                        } else {
                            $this->card = null;
                        }

                    } else {
                        // this is only the case for adyen_cc payments
                        $this->card->cvc = $payment->getCcCid();
                        $this->card->expiryMonth = $payment->getCcExpMonth();
                        $this->card->expiryYear = $payment->getCcExpYear();
                        $this->card->holderName = $payment->getCcOwner();
                        $this->card->number = $payment->getCcNumber();
                    }
				}

                // installments
                if(Mage::helper('adyen/installments')->isInstallmentsEnabled() &&  $payment->getAdditionalInformation('number_of_installments') > 0){
                    $this->installments = new Adyen_Payment_Model_Adyen_Data_Installments();
                    $this->installments->value = $payment->getAdditionalInformation('number_of_installments');
                }

            if ($paymentMethod == "multibanco") {
                $this->card = $this->deliveryAddress = $this->recurring = $this->additionalData = null;

                $this->selectedBrand = $paymentMethod;

                $this->deliveryDate = $payment->getAdditionalInformation('delivery_date');
            }

                // add observer to have option to overrule and or add request data
                Mage::dispatchEvent('adyen_payment_card_payment_request', array('order' => $order, 'paymentMethod' => $paymentMethod, 'paymentRequest' => $this));

                break;
            case "boleto":
            	$boleto = unserialize($payment->getPoNumber());
            	$this->card = null;
                $this->bankAccount = null;
            	$this->socialSecurityNumber = $boleto['social_security_number'];
            	$this->selectedBrand = $boleto['selected_brand'];
            	$this->shopperName->firstName = $boleto['firstname'];
            	$this->shopperName->lastName = $boleto['lastname'];
            	$this->deliveryDate = $boleto['delivery_date'];
            	break;
            case "sepa":
                $this->card = null;
                $this->shopperName = null;
                $this->bankAccount->iban = $payment->getAdditionalInformation('iban');
                $this->bankAccount->ownerName = $payment->getAdditionalInformation('account_name');
                $this->bankAccount->countryCode = $payment->getAdditionalInformation('country');
                $this->selectedBrand = "sepadirectdebit";
                break;
        }
        return $this;
    }

}
