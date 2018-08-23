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

class Adyen_Payment_ApplePayController extends Mage_Core_Controller_Front_Action
{

    /**
     * @return mixed
     * @throws Exception
     */
    public function requestMerchantSessionAction()
    {
        $params = $this->getRequest()->getParams();

//        $validationUrl = $params['validationURL'];
        // Works for test and live. Maybe we need to switch for validationUrl from callback event waiting for apple to respond
        $validationUrl = "https://apple-pay-gateway-cert.apple.com/paymentservices/startSession";
        
        // create a new cURL resource
        $ch = curl_init();

        $merchantIdentifier = Mage::helper('adyen')->getApplePayMerchantIdentifier();

        $domainName = $_SERVER['SERVER_NAME'];
        $displayName = Mage::app()->getStore()->getName();

        $data = '{
            "merchantIdentifier":"'. $merchantIdentifier . '",
            "domainName":"'. $domainName . '",
            "displayName":"'. $displayName . '"
        }';

        curl_setopt($ch, CURLOPT_URL, $validationUrl);

        // location applepay certificates
        $fullPathLocationPEMFile = Mage::helper('adyen')->getApplePayFullPathLocationPEMFile();

        curl_setopt($ch, CURLOPT_SSLCERT, $fullPathLocationPEMFile);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );

        $result = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $message = curl_error($ch);

        if ($httpStatus != 200 && $result) {
            Mage::log("Check if your PEM file location is correct location is now defined:" . $fullPathLocationPEMFile, Zend_Log::ERR, 'adyen_exception.log');
            Mage::log("Apple Merchant Valdiation Failed. Please check merchantIdentifier, domainname and PEM file. Request is: " . var_export($data,true) . "RESULT:" . $result . " HTTPS STATUS:" . $httpStatus . "VALIDATION URL:" . $validationUrl, Zend_Log::ERR, 'adyen_exception.log');
        } elseif(!$result) {
            $errno = curl_errno($ch);
            $message = curl_error($ch);

            curl_close($ch);

            $msg = "\n(Network error [errno $errno]: $message)";
            Mage::log($msg, Zend_Log::ERR, 'adyen_exception.log');
            throw new \Exception($msg);
        }

        // close cURL resource, and free up system resources
        curl_close($ch);

        return $result;
    }

    /**
     * @return $this
     */
    public function retrieveShippingMethodsAction()
    {
        $params = $this->getRequest()->getParams();

        // allow empty parameters because this can happen if you have an invalid address in wallet on phone
        if(isset($params['country'])) {
            $country = $params['country'];
        } else {
            $country = "";
        }

        if(isset($params['zipcode'])) {
            $zipcode = $params['zipcode'];
        } else {
            $zipcode = "";
        }

        if(isset($params['productId'])) {
            $productId = $params['productId'];
        } else {
            $productId = "";
        }

        if(isset($params['qty'])) {
            $qty = $params['qty'];
        } else {
            $qty = 1;
        }

        // is it from the cart or from a product ??
        // needs to be done for setting payment method!
        if ($productId != "" && $productId > 0) {

            $shippingCosts = $this->calculateShippingCosts($productId, $country, Mage::app()->getStore()->getId(), $qty);
            $costs = array();
            foreach ($shippingCosts as $identifier => $shippingCost) {
                $costs[] = array(
                    'label' => trim($shippingCost['title']),
                    'detail' => '',
                    'amount' => $shippingCost['price'],
                    'identifier' => $identifier
                );
            }

            $this->getResponse()->setBody(json_encode($costs));
            return $this;

        } else {
            $cart = Mage::getSingleton('checkout/cart');
            $address = $cart->getQuote()->getShippingAddress();
            $address->setCountryId($country)
                ->setPostcode($zipcode)
                ->setCollectShippingrates(true);
            $cart->save();

            // Find if our shipping has been included.
            $rates = $address->collectShippingRates()
                ->getGroupedAllShippingRates();

            $costs = array();
            foreach ($rates as $carrier) {
                foreach ($carrier as $rate) {
                    $costs[] = array(
                        'label' => trim($rate->getCarrierTitle()),
                        'detail' => '',
                        'amount' => $rate->getPrice(),
                        'identifier' => $rate->getCode()
                    );
                }
            }
        }
        
        $this->getResponse()->setBody(json_encode($costs));
        return $this;
    }

    /**
     * @param $productId
     * @param $country
     * @param int $storeId
     * @param int $qty
     * @return array
     */
    public function calculateShippingCosts($productId, $country, $storeId = 1, $qty = 1)
    {
        $product = Mage::getModel('catalog/product')->load($productId);
        $item = Mage::getModel('sales/quote_item')->setProduct($product)->setQty(1);
        $store = Mage::getModel('core/store')->load($storeId);

        $request = Mage::getModel('shipping/rate_request')
            ->setAllItems(array($item))
            ->setDestCountryId($country)
            ->setPackageValue($product->getFinalPrice())
            ->setPackageValueWithDiscount($product->getFinalPrice())
            ->setPackageWeight($product->getWeight())
            ->setPackageQty($qty)
            ->setPackagePhysicalValue($product->getFinalPrice())
            ->setFreeMethodWeight(0)
            ->setStoreId($store->getId())
            ->setWebsiteId($store->getWebsiteId())
            ->setFreeShipping(0)
            ->setBaseCurrency($store->getBaseCurrency())
            ->setBaseSubtotalInclTax($product->getFinalPrice());

        $model = Mage::getModel('shipping/shipping')->collectRates($request);
        $costs = array();

        foreach($model->getResult()->getAllRates() as $shippingRate) {

            $rate = Mage::getModel('sales/quote_address_rate')
                ->importShippingRate($shippingRate);


            $costs[$rate->getCode()] = array(
                'title' => trim($rate->getCarrierTitle()),
                'price' => $rate->getPrice()
            );
        }
        return $costs;
    }


    /**
     * @return $this
     */
    public function sendPaymentAction()
    {
        $params = $this->getRequest()->getParams();
        
        // check if payment is set
        if (!isset($params['payment']) || $params['payment'] == "") {
            Mage::throwException(Mage::helper('adyen')->__('Missing param payment'));
        }

        if (!isset($params['qty']) || $params['qty'] == "") {
            Mage::throwException(Mage::helper('adyen')->__('Missing param qty'));
        }

        $qty = $params['qty'];
        $shippingMethod = $params['shippingMethod'];
        $payment = json_decode($params['payment']);


        // check if token is in paymentDetails
        if (!isset($payment->token->paymentData) || $payment->token->paymentData == "") {
            Mage::throwException(Mage::helper('adyen')->__('Missing token in payment'));
        }

        $token = json_encode($payment->token->paymentData);

        if(isset($params['productId']) && $params['productId'] > 0) {
            $productId = $params['productId'];
            $quote = Mage::getModel('sales/quote');
        } else {
            // load quote from session
            $quote = Mage::getSingleton('checkout/session')->getQuote();
        }

        // check if user is loggedin
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $quote = $quote->assignCustomer($customer);
        }

        // override shippingContact and DeliveryContact
        if($payment->billingContact && $payment->shippingContact) {
            try {
                $this->updateBillingAddress($quote, $payment->billingContact, $payment->shippingContact);
            } catch(Exception $e) {
                Mage::logException($e);
                $this->getResponse()->setBody("ERROR BILLING");
                return $this;
            }

            try {
                $this->updateShippingAddress($quote, $payment->shippingContact);
            } catch(Exception $e) {
                Mage::logException($e);
                $this->getResponse()->setBody("ERROR SHIPPING");
                return $this;
            }
        }

        // needs to be done for setting payment method!
        if(isset($params['productId']) && $params['productId'] > 0) {
            $product = Mage::getModel('catalog/product')->load($productId);
            $quote->addProduct($product , $qty);
        }
        
        if($shippingMethod)
        {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates()
                ->setShippingMethod($shippingMethod)
                ->setPaymentMethod('adyen_apple_pay');
        }
        
        $quote->getPayment()->importData(array('method' => 'adyen_apple_pay', 'token' => $token, 'allow_apple_pay' => '1'));
        $quote->collectTotals();
        $quote->save();
        $service = Mage::getModel('sales/service_quote', $quote);
        
        try {
            $service->submitAll();
            $order = $service->getOrder();
            $order->save();
            
            $result = "SUCCESS";

            // add order information to the session
            $session = Mage::getSingleton('checkout/session');
            $session->setLastOrderId($order->getId());
            $session->setLastRealOrderId($order->getIncrementId());
            $session->setLastSuccessQuoteId($order->getQuoteId());
            $session->setLastQuoteId($order->getQuoteId());
            $session->unsAdyenRealOrderId();
            $session->setQuoteId($session->getAdyenQuoteId(true));
            $session->getQuote()->setIsActive(false)->save();


        } catch (Exception $e) {
            Mage::logException($e);
            $result = "ERROR";
        }

        $this->getResponse()->setBody($result);
        return $this;
    }

    /**
     * @param $quote
     * @param $billingContact
     * @param $shippingContact
     */
    protected function updateBillingAddress($quote, $billingContact, $shippingContact)
    {
        $addressLines = $billingContact->addressLines;
        $size = count($billingContact->addressLines);
        if($size > 1) {
            $billingStreet = implode("\n", $addressLines);
        } else {
            $billingStreet = $addressLines[0];
        }

        // billing phonenumber can be empty
        if (isset($billingContact->phoneNumber)) {
            $billingPhone = $billingContact->phoneNumber;
        } else {
            $billingPhone = $shippingContact->phoneNumber;
        }

        $regionId = '';
        if ($billingContact->administrativeArea && $billingContact->countryCode) {
            $region = Mage::getModel('directory/region')->loadByCode($billingContact->administrativeArea, $billingContact->countryCode);
            $regionId = $region->getId();
        }

        $countryId = Mage::getModel('directory/country')
            ->loadByCode($billingContact->countryCode)
            ->getId();

        $billingAddress = array(
            'firstname' => $billingContact->givenName,
            'lastname' =>  $billingContact->familyName,
            'street' => $billingStreet,
            'city' => $billingContact->locality,
            'country_id' => $countryId,
            'region_id' => $regionId,
            'postcode' => $billingContact->postalCode,
            'telephone' => $billingPhone,
        );

        $billingAddress = Mage::getModel('sales/quote_address')
            ->setData($billingAddress)
            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING);

        $quote->setBillingAddress($billingAddress);

        $addressValidation = $quote->getBillingAddress()->validate();
        if ($addressValidation !== true) {
            Mage::log("Billing Contract:" . print_r($billingContact, 1), Zend_Log::DEBUG, 'adyen_apple_pay.log');
            Mage::log("Billing Validation Error" . print_r($addressValidation, 1) . print_r($billingAddress, 1), Zend_Log::DEBUG, 'adyen_apple_pay.log');
            Mage::throwException(Mage::helper('adyen')->__('Error Billing address validation'));
        }
    }

    /**
     * @param $quote
     * @param $billingContact
     * @param $shippingContact
     */
    protected function updateShippingAddress($quote, $shippingContact)
    {
        // SHIPPING
        $addressLines = $shippingContact->addressLines;
        $size = count($shippingContact->addressLines);
        if($size > 1) {
            $street = implode("\n", $addressLines);
        } else {
            $street = $addressLines[0];
        }

        $regionId = '';
        if ($shippingContact->administrativeArea && $shippingContact->countryCode) {
            $region = Mage::getModel('directory/region')->loadByCode($shippingContact->administrativeArea, $shippingContact->countryCode);
            $regionId = $region->getId();
        }

        $countryId = Mage::getModel('directory/country')
            ->loadByCode($shippingContact->countryCode)
            ->getId();

        $shippingAddress = array(
            'firstname' => $shippingContact->givenName,
            'lastname' =>  $shippingContact->familyName,
            'street' => $street,
            'city' => $shippingContact->locality,
            'country_id' => $countryId,
            'region_id' => $regionId,
            'postcode' => $shippingContact->postalCode,
            'telephone' => $shippingContact->phoneNumber,
        );

        $shippingAddress = Mage::getModel('sales/quote_address')
            ->setData($shippingAddress)
            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING);

        $quote->setShippingAddress($shippingAddress);

        $addressValidation = $quote->getShippingAddress()->validate();
        if ($addressValidation !== true) {
            Mage::log("Shipping Validation Error" . print_r($addressValidation, 1), Zend_Log::DEBUG, 'adyen_apple_pay.log');
            Mage::throwException(Mage::helper('adyen')->__('Error Shipping address validation'));
        }
    }
}
