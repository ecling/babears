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

class Adyen_Payment_CheckoutPosController extends Mage_Core_Controller_Front_Action {

    public function indexAction()
    {
        $params = $this->getRequest()->getParams();

        $store = Mage::app()->getStore();

        // if recurring card is selected do online OneClick Payment
        $recurringDetailReference = isset($params['recurringDetailReference']) ? $params['recurringDetailReference'] : "";

        if($recurringDetailReference) {
            // load customer by Id
            $customerId = isset($params['customerId']) ? $params['customerId'] : "";
            $customerObject = Mage::getModel("customer/customer")->load($customerId);
        } else {
            // check if email is filled in
            $adyenPosEmail = isset($params['adyenPosEmail']) ? $params['adyenPosEmail'] : "";
            $saveCard = isset($params['adyenPosSaveCard']) ? $params['adyenPosSaveCard'] : "";

            if($adyenPosEmail != "") {

                // check if the email hasa an existing account
                $customer = Mage::getModel("customer/customer");
                $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
                $customer->loadByEmail($adyenPosEmail);

                if($customer->getId() > 0) {
                    // customer exists so give back customerData
                    $customerObject = $customer;
                } else {
                    // create new account with provided email
                    $websiteId = Mage::app()->getWebsite()->getId();

                    $customer = Mage::getModel("customer/customer");

                    $password = Mage::helper('core')->getRandomString($length = 8);


                    $customer->setWebsiteId($websiteId)
                        ->setStore($store)
                        ->setEmail($adyenPosEmail)
                        ->setPassword($password);

                    try{
                        $customer->save();
                        $customerObject = $customer;
                    }
                    catch (Exception $e) {
                        Zend_Debug::dump($e->getMessage());
                    }
                }
            } else {
                $customer = Mage::getSingleton('customer/session');
                // no email is filled in so connect this to current logged in user:
                if($customer->isLoggedIn()) {
                    $customerObject = Mage::getModel('customer/customer')->load($customer->getId());
                } else {
                    Mage::throwException('Customer is not logged in.');
                }
            }
        }

        // get email

        $quote = (Mage::getModel('checkout/type_onepage') !== false)? Mage::getModel('checkout/type_onepage')->getQuote(): Mage::getModel('checkout/session')->getQuote();

        // important update the shippingaddress and billingaddress this can be null sometimes.
        $quote->assignCustomerWithAddressChange($customerObject);

        $shippingAddress = $quote->getShippingAddress();

        $shippingAddress->setCollectShippingRates(true)->collectShippingRates()
            ->setShippingMethod('freeshipping_freeshipping')
            ->setPaymentMethod('adyen_pos');


        if($recurringDetailReference) {
            $store->setConfig('payment/adyen_oneclick/active', 1);

            // set config to recurring because we want to do a recurring transaction in this case
            $store->setConfig('payment/adyen_abstract/recurringtypes', 'RECURRING');

            // do the payment
            $quote->getPayment()->importData(array('method' => 'adyen_oneclick', 'recurring_detail_reference' => $recurringDetailReference));
        } else {
            $quote->getPayment()->importData(array('method' => 'adyen_pos', 'store_cc' => $saveCard));
        }


        $quote->collectTotals()->save();
        $session = Mage::getSingleton('checkout/session');

        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();
        $order = $service->getOrder();

        $oderStatus = Mage::helper('adyen')->getOrderStatus();
        $order->setStatus($oderStatus);
        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $order->save();

        // add order information to the session
        $session->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastSuccessQuoteId($order->getQuoteId())
            ->setLastQuoteId($order->getQuoteId());

        if($recurringDetailReference) {
            $this->_redirect('checkout/onepage/success');
        } else {
            $this->_redirect('adyen/process/redirect');
        }

        return $this;
    }

    public function validateCustomerByEmailAction()
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $jsonData = array();

        if($this->_hasExpressCheckout() && $this->_inKioskMode() != "1") {
            $params = $this->getRequest()->getParams();
            $email = isset($params['email']) ? $params['email'] : "";
            $customerId = isset($params['customerId']) ? $params['customerId'] : "";

            if($customerId > 0) {

                $customer = Mage::getModel("customer/customer")->load($customerId);
                $jsonData['customerData'] = $customer->getData();

            } else {

                $customer = Mage::getModel("customer/customer");
                $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
                $customer->loadByEmail($email);

                if($customer->getId() > 0) {

                    $jsonData['customerData'] = $customer->getData();
                }
            }


            if($customer->getId() > 0) {
                // do list recurring call
                $adyenHelper = Mage::helper('adyen');

                $store = Mage::app()->getStore();

                $merchantAccount = trim($adyenHelper->getConfigData('merchantAccount', 'adyen_abstract', $store->getId()));
//            $recurringType = $adyenHelper->getConfigData('recurringtypes', 'adyen_abstract', $store->getId());
                // you only want recurring cards so you can select the card to do online payment
                $recurringType = "RECURRING";

                try {
                    $recurringCards = $adyenHelper->getRecurringCards($merchantAccount, $customerId, $recurringType);
                    $jsonData['recurringCards'] = $recurringCards;
                } catch(Exception $e) {
                    // do nothing
                }
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonData));
    }

    public function getCustomerEmailAction()
    {
        $jsonData = "";

        if($this->_hasExpressCheckout() && $this->_inKioskMode() != "1") {
            $params = $this->getRequest()->getParams();
            $email = isset($params['email']) ? $params['email'] : "";

            $customers = Mage::getModel('customer/customer')->getCollection()
                ->addAttributeToSelect('email')
                ->addAttributeToFilter('email', array('like'=>'%'.$email.'%'))
                ->addAttributeToFilter('website_id', Mage::app()->getStore()->getWebsiteId());

            $jsonData = '<ul>';
            foreach ($customers as $customer) {
                $data = $customer->getData();
                $id = $customer->getId();
                $jsonData .= '<li id="customer-'.$id.'">' . $data['email'] . '</li>';
            }
            $jsonData .= '</ul>';
        }
        $this->getResponse()->setBody($jsonData);
    }

    protected function _hasExpressCheckout()
    {
        // must be login to show this checkout option
        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            return (string) Mage::helper('adyen')->hasExpressCheckout();
        } else {
            return false;
        }
    }

    protected function _inKioskMode()
    {
        return Mage::helper('adyen')->getConfigData("express_checkout_kiosk_mode", "adyen_pos", null);
    }

}
