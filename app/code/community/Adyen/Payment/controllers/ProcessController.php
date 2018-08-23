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
 * @category    Adyen
 * @package    Adyen_Payment
 * @copyright    Copyright (c) 2011 Adyen (http://www.adyen.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */
class Adyen_Payment_ProcessController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Adyen_Payment_Model_Adyen_Data_Server
     */
    const SOAP_SERVER = 'Adyen_Payment_Model_Adyen_Data_Server_Notification';

    const OPENINVOICE_SOAP_SERVER = 'Adyen_Payment_Model_Adyen_Data_Server_Openinvoice';

    /**
     * Redirect Block
     * need to be redeclared
     */
    protected $_redirectBlockType = 'adyen/redirect';

    /**
     * @desc Soap Interface/Webservice
     * @since 0.0.9.9r2
     */
    public function soapAction()
    {
        $classmap = Mage::getModel('adyen/adyen_data_notificationClassmap');
        $server = new SoapServer($this->_getWsdl(), array('classmap' => $classmap));
        $server->setClass(self::SOAP_SERVER);
        $server->addFunction(SOAP_FUNCTIONS_ALL);
        $server->handle();

        //get soap request
        $request = Mage::registry('soap-request');

        if (empty($request)) {
            $this->_return401();
            return;
        }

        $result = "";
        if (is_array($request->notification->notificationItems->NotificationRequestItem)) {
            foreach ($request->notification->notificationItems->NotificationRequestItem as $item) {
                $item = $this->formatSoapNotification($item);
                $result = $this->processNotification($item);
            }
        } else {
            $item = $request->notification->notificationItems->NotificationRequestItem;
            $item = $this->formatSoapNotification($item);
            $result = $this->processNotification($item);
        }

        if (!empty($result['response']) && $result['response'] == "401") {
            $this->getResponse()->setBody($result['message']);
            $this->_return401();
            return;
        }

        return $this;
    }


    /**
     * @desc Format soap notification so it is allign with HTTP POST and JSON output
     * @param $item
     * @return mixed
     */
    protected function formatSoapNotification($item)
    {
        $additionalData = array();
        foreach ($item->additionalData->entry as $additionalDataItem) {
            $key = $additionalDataItem->key;
            $value = $additionalDataItem->value;

            if (strpos($key, '.') !== false) {
                $results = explode('.', $key);
                $size = count($results);
                if ($size == 2) {
                    $additionalData[$results[0]][$results[1]] = $value;
                } elseif ($size == 3) {
                    $additionalData[$results[0]][$results[1]][$results[2]] = $value;
                }
            } else {
                $additionalData[$key] = $value;
            }
        }
        $item->additionalData = $additionalData;
        return $item;
    }

    /**
     * @desc Get Wsdl
     * @since 0.0.9.9r2
     * @return string
     */
    protected function _getWsdl()
    {
        return Mage::getModuleDir('etc', 'Adyen_Payment') . DS . 'Notification.wsdl';
    }

    public function redirectAction()
    {
        try {
            $session = $this->_getCheckout();
            $order = $this->_getOrder();
            $quoteId = $session->getQuoteId();

            $session->setAdyenQuoteId($quoteId);
            $session->setAdyenRealOrderId($session->getLastRealOrderId());
            $order->loadByIncrementId($session->getLastRealOrderId());

            //redirect only if this order is never recorded
            $hasEvent = Mage::getResourceModel('adyen/adyen_event')
                ->getLatestStatus($session->getLastRealOrderId());
            if (!empty($hasEvent) || !$order->getId() || empty($quoteId)) {
                $this->_redirect('/');
                return $this;
            }

            Mage::log($session->getData(),null,'success-c.log');

            // redirect to payment page
            $this->getResponse()->setBody(
                $this->getLayout()
                    ->createBlock($this->_redirectBlockType)
                    ->setOrder($order)
                    ->toHtml()
            );

            $session->setQuoteId(null);
        } catch (Exception $e) {
            $session->addException($e, Mage::helper('adyen')->__($e->getMessage()));
            $this->cancel();
        }
    }

    public function validate3dAction()
    {

        // get current order
        $session = $this->_getCheckout();

        try {
            $order = $this->_getOrder();
            $session->setAdyenQuoteId($session->getQuoteId());
            $session->setAdyenRealOrderId($session->getLastRealOrderId());
            $order->loadByIncrementId($session->getLastRealOrderId());
            $adyenStatus = $order->getAdyenEventCode();

            // get payment details
            $payment = $order->getPayment();

            if ($payment === false) {
                throw new Exception('No payment information available');
            }

            $paRequest = $payment->getAdditionalInformation('paRequest');
            $md = $payment->getAdditionalInformation('md');
            $issuerUrl = $payment->getAdditionalInformation('issuerUrl');

            $infoAvailable = $payment && !empty($paRequest) && !empty($md) && !empty($issuerUrl);

            // check adyen status and check if all information is available
            if (!empty($adyenStatus) && $adyenStatus == 'RedirectShopper' && $infoAvailable) {
                $request = $this->getRequest();
                $requestMD = $request->getPost('MD');
                $requestPaRes = $request->getPost('PaRes');

                // authorise the payment if the user is back from the external URL
                if ($request->isPost() && !empty($requestMD) && !empty($requestPaRes)) {
                    if ($requestMD == $md) {
                        $payment->setAdditionalInformation('paResponse', $requestPaRes);
                        // send autorise3d request, catch exception in case of 'Refused'
                        try {
                            $result = $payment->getMethodInstance()->authorise3d($payment, $order->getGrandTotal());
                        } catch (Exception $e) {
                            $result = 'Refused';
                            $order->setAdyenEventCode($result)->save();
                        }

                        // check if authorise3d was successful
                        if ($result == 'Authorised') {
                            $order->addStatusHistoryComment(
                                Mage::helper('adyen')->__('3D-secure validation was successful'),
                                $order->getStatus()
                            )->save();

                            $session->unsAdyenRealOrderId();
                            $session->setQuoteId($session->getAdyenQuoteId(true));
                            $session->getQuote()->setIsActive(false)->save();

                            // add success to additionalData so you know that for this order 3D was successful
                            $order->getPayment()->setAdditionalInformation('3d_successful', true);
                            $order->save();

                            $this->_redirect('checkout/onepage/success', array('utm_nooverride' => '1'));
                        } else {
                            // only cancel if 3D secure was not already successful
                            if (!$order->getPayment()->getAdditionalInformation('3d_successful')) {
                                $order->addStatusHistoryComment(Mage::helper('adyen')->__('3D-secure validation was unsuccessful.'))->save();
                                $session->addException($e, Mage::helper('adyen')->__($e->getMessage()));
                                $this->cancel();
                            } else {
                                // already succesfull so turn back to the success page
                                $this->_redirect('checkout/onepage/success', array('utm_nooverride' => '1'));
                            }
                        }
                    } else {
                        $errorMsg = Mage::helper('adyen')->__('3D secure validation error');
                        Adyen_Payment_Exception::throwException($errorMsg);
                    }
                } // otherwise, redirect to the external URL
                else {
                    $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true,
                        Mage::helper('adyen')->__('Customer was redirected to bank for 3D-secure validation. Once the shopper authenticated, the order status will be updated accordingly. 
                        <br />Make sure that your notifications are being processed! 
                        <br />If the order is stuck on this status, the shopper abandoned the session. The payment can be seen as unsuccessful. 
                        <br />The order can be automatically cancelled based on the OFFER_CLOSED notification. Please contact Adyen Support to enable this.'))->save();
                    $this->getResponse()->setBody(
                        $this->getLayout()->createBlock($this->_redirectBlockType)->setOrder($order)->toHtml()
                    );
                }
            } else {
                // log exception
                $errorMsg = Mage::helper('adyen')->__('3D secure went wrong');

                if ($order) {
                    $errorMsg .= " for orderId: " . $order->getId();
                }

                if ($adyenStatus) {
                    $errorMsg .= " adyenStatus is: " . $adyenStatus;
                }

                Adyen_Payment_Exception::throwException($errorMsg);
            }
        } catch (Exception $e) {
            $alreadySuccessful = $order->getPayment() && $order->getPayment()->getAdditionalInformation('3d_successful');
            // ignore because 3d was already successful for this order
            if (!$alreadySuccessful) {
                Mage::logException($e);
                $this->cancel();
            } else {
                $this->_redirect('checkout/onepage/success', array('utm_nooverride' => '1'));
            }
        }
    }

    /**
     * Adyen returns POST variables to this action
     */
    public function successAction()
    {
        // get the response data
        $response = $this->getRequest()->getParams();

        // process
        try {
            $result = $this->validateResultUrl($response);

            if ($result) {
                $session = $this->_getCheckout();

                //当变换浏览器跳转时传递订单数
                if(isset($response['merchantReference'])){
                    $incrementId = $response['merchantReference'];
                    $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
                    $session->setLastRealOrderId($incrementId);
                    $session->setLastSuccessQuoteId($order->getQuoteId());
                    $session->setLastQuoteId($order->getQuoteId());
                    $session->setLastOrderId($order->getId());
                }

                $session->unsAdyenRealOrderId();
                $session->setQuoteId($session->getAdyenQuoteId(true));
                $session->getQuote()->setIsActive(false)->save();
                Mage::log($session->getData(),null,'success-d.log');
                $this->_redirect('checkout/onepage/success', array('utm_nooverride' => '1'));
            } else {
                $this->cancel();
            }
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
    }

    public function successPosRedirectAction()
    {
        $session = $this->_getCheckout();

        // clear session for email shopper
        $session->setAdyenEmailShopper("");

        $session->unsAdyenRealOrderId();
        $session->setQuoteId($session->getAdyenQuoteId(true));
        $session->getQuote()->setIsActive(false)->save();
        $this->_redirect('checkout/onepage/success', array('utm_nooverride' => '1'));
    }

    /**
     * @desc reloads the items in the cart && cancel the order
     * @since v009
     */
    public function cancel()
    {

        $session = $this->_getCheckout();

        // clear session for email shopper
        $session->setAdyenEmailShopper("");

        $order = Mage::getModel('sales/order');
        $incrementId = $session->getLastRealOrderId();

        if (empty($incrementId)) {
            $session->addError($this->__('Your payment failed, Please try again later'));
            $this->_redirectCheckoutCart();
            return;
        }

        $order->loadByIncrementId($incrementId);

        // Don't cancel if the order had been authorised
        if ($order->getAdyenEventCode() == Adyen_Payment_Model_Event::ADYEN_EVENT_AUTHORISED) {
            $session->addError($this->__('Your payment has been already processed'));
            $this->_redirectCheckoutCart();
            return;
        }

        // reactivate the quote again
        $quoteId = $order->getQuoteId();
        $quote = Mage::getModel('sales/quote')
            ->load($quoteId)
            ->setIsActive(1)
            ->save();

        // reset reserverOrderId because already used by previous order
        $quote->setReservedOrderId(null);
        $session->replaceQuote($quote);

        // if setting failed_attempt_disable is on and the payment method is openinvoice ignore this payment mehthod the second time
        if ($this->_getConfigData('failed_attempt_disable', 'adyen_openinvoice') && $order->getPayment()->getMethod() == "adyen_openinvoice") {
            // check if payment failed
            $response = $this->getRequest()->getParams();
            if ($response['authResult'] == "REFUSED") {
                $session->setOpenInvoiceInactiveForThisQuoteId($quoteId);
            }
        }

        //handle the old order here
        $orderStatus = $this->_getConfigData('payment_cancelled', 'adyen_abstract', $order->getStoreId());

        try {
            $order->setActionFlag($orderStatus, true);
            switch ($orderStatus) {
                case Mage_Sales_Model_Order::STATE_HOLDED:
                    if ($order->canHold()) {
                        $order->hold()->save();
                    }
                    break;
                default:
                    if ($order->canCancel()) {
                        $order->cancel()->save();
                    }
                    break;
            }
        } catch (Mage_Core_Exception $e) {
            Adyen_Payment_Exception::logException($e);
        }

        $params = $this->getRequest()->getParams();
        if (isset($params['authResult']) && $params['authResult'] == Adyen_Payment_Model_Event::ADYEN_EVENT_CANCELLED) {
            $session->addError($this->__('You have cancelled the order. Please try again'));
        } elseif ($order->getPayment()->getMethod() == "adyen_openinvoice") {
            $session->addError($this->__('Your openinvoice payment failed'));
        } else {
            $session->addError($this->__('Your payment failed, Please try again later'));
        }

        // if payment method is adyen_pos or adyen_cash redirect to checkout if the kiosk mode is turned off
        if (!$this->_getConfigData('express_checkout_kiosk_mode', 'adyen_pos') && ($order->getPayment()->getMethod() == "adyen_pos" || $order->getPayment()->getMethod() == "adyen_cash")) {

            // add email to session so this can be shown
            $session->setAdyenEmailShopper($order->getCustomerEmail());

            $redirect = Mage::getUrl('checkout/cart');
            $this->_redirectUrl($redirect);
        } else {
            $this->_redirectCheckoutCart();
        }
    }

    protected function _redirectCheckoutCart()
    {
        $redirect = Mage::getStoreConfig('payment/adyen_abstract/payment_cancelled_redirect');

        if ($redirect == "checkout/cart") {
            $redirect = Mage::getUrl('checkout/cart');
            $this->_redirectUrl($redirect);
        } elseif ($redirect == "checkout/onepage") {
            $redirect = Mage::helper('checkout/url')->getCheckoutUrl();
            $this->_redirectUrl($redirect);
        } else {
            $this->_redirect($redirect);
        }
    }

    public function insAction()
    {
        try {
            // if version is in the notification string show the module version
            $response = $this->getRequest()->getParams();
            if (isset($response['version'])) {
                $helper = Mage::helper('adyen');
                $this->getResponse()->setBody($helper->getExtensionVersion());
                return $this;
            }

            if (isset($response['magento_version'])) {
                $version = Mage::getVersion();
                $this->getResponse()->setBody($version);
                return $this;
            }

            $notificationMode = isset($response['live']) ? $response['live'] : "";

            if ($notificationMode != "" && $this->_validateNotificationMode($notificationMode)) {
                // add HTTP POST attributes as an array so it is the same as JSON and SOAP result
                foreach ($response as $key => $value) {
                    if (strpos($key, '_') !== false) {
                        $results = explode('_', $key);
                        $size = count($results);
                        if ($size == 2) {
                            $response[$results[0]][$results[1]] = $value;
                        } elseif ($size == 3) {
                            $response[$results[0]][$results[1]][$results[2]] = $value;
                        }
                    }
                }

                // create amount array so it is the same as JSON SOAP response
                $response['amount'] = array('value' => $response['value'], 'currency' => $response['currency']);

                $result = $this->processNotification($response);

                if (!empty($result['response']) && $result['response'] == "401") {

                    $this->getResponse()->setBody($result['message']);
                    $this->_return401();
                    return;
                } else {
                    $this->getResponse()
                        ->clearHeader('Content-Type')
                        ->setHeader('Content-Type', 'text/html')
                        ->setBody("[accepted]");
                    return;
                }
            } else {
                if ($notificationMode == "") {
                    $this->_return401();
                    return;
                }

                Mage::throwException(
                    Mage::helper('adyen')->__('Mismatch between Live/Test modes of Magento store and the Adyen platform.')
                );
                return;
            }

        } catch (Exception $e) {
            Adyen_Payment_Exception::logException($e);
        }
        return $this;
    }

    public function jsonAction()
    {
        try {
            $notificationItems = json_decode(file_get_contents('php://input'), true);

            $notificationMode = isset($notificationItems['live']) ? $notificationItems['live'] : "";

            if ($notificationMode != "" && $this->_validateNotificationMode($notificationMode)) {
                foreach ($notificationItems['notificationItems'] as $notificationItem) {
                    $result = $this->processNotification($notificationItem['NotificationRequestItem']);
                       if (!empty($result['response']) && $result['response'] == "401") {
                        $this->getResponse()->setBody($result['message']);
                        $this->_return401();
                        return;
                    }
                }
                $acceptedMessage = "[accepted]";
                $cronCheckTest = $notificationItems['notificationItems'][0]['NotificationRequestItem']['pspReference'];
                // Run the query for checking unprocessed notifications, do this only for test notifications coming from the Adyen Customer Area
                if ($this->_isTestNotification($cronCheckTest)) {
                    $unprocessedNotifications = Mage::helper('adyen')->getUnprocessedNotifications();
                    if ($unprocessedNotifications > 0) {
                        $acceptedMessage .= "\nYou have " . $unprocessedNotifications . " unprocessed notifications.";
                    }
                }
                $this->getResponse()
                    ->clearHeader('Content-Type')
                    ->setHeader('Content-Type', 'text/html')
                    ->setBody($acceptedMessage);
                return;
            } else {
                if ($notificationMode == "") {
                    $this->_return401();
                    return;
                }

                Mage::throwException(
                    Mage::helper('adyen')->__('Mismatch between Live/Test modes of Magento store and the Adyen platform')
                );
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /* START actions for POS */
    public function successPosAction()
    {
        $response = $this->getRequest()->getParams();
        $result = $this->processPosResponse($response);

        if ($result) {
            $session = $this->_getCheckout();
            $session->unsAdyenRealOrderId();
            $session->setQuoteId($session->getAdyenQuoteId(true));
            $session->getQuote()->setIsActive(false)->save();
            $this->_redirect('checkout/onepage/success', array('utm_nooverride' => '1'));
        } else {
            $this->cancel();
        }
    }

    public function getOrderStatusAction()
    {
        $merchantReference = $this->getRequest()->getParam('merchantReference');
        $result = Mage::getModel('adyen/getPosOrderStatus')->hasApprovedOrderStatus($merchantReference);

        $response = "";

        if ($result) {
            $response = 'true';
        }

//        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        $this->getResponse()->setBody($response);

        return $this;
    }

    public function cancelAction()
    {
        $this->cancel();
    }


    public function processPosResponse($response)
    {
        return Mage::getModel('adyen/processPosResult')->processPosResponse($response);
    }

    /* END actions for POS */

    protected function _return401()
    {
        $this->getResponse()->setHttpResponseCode(401);
    }

    public function processNotification($response)
    {
        return Mage::getModel('adyen/processNotification')->processResponse($response);
    }

    public function validateResultUrl($response)
    {
        return Mage::getModel('adyen/validateResultUrl')->validateResponse($response);
    }

    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getOrder()
    {
        return Mage::getModel('sales/order');
    }

    protected function _validateNotificationMode($notificationMode)
    {
        $mode = $this->_getConfigData('demoMode');
        if ($mode == 'Y' && $notificationMode == "false" || $mode == 'N' && $notificationMode == 'true') {
            return true;
        }
        return false;
    }

    /**
     * @desc Give Default settings
     * @param $code
     * @param null $paymentMethodCode
     * @param null $storeId
     * @return mixed
     */
    protected function _getConfigData($code, $paymentMethodCode = null, $storeId = null)
    {
        return Mage::helper('adyen')->_getConfigData($code, $paymentMethodCode, $storeId);
    }

    /**
     * If notification is a test notification from Adyen Customer Area
     ** @param $pspReference
     * @return bool
     */
    protected function _isTestNotification($pspReference)
    {
        if (strpos(strtolower($pspReference), "test_") !== false
            || strpos(strtolower($pspReference), "testnotification_") !== false
        ) {
            return true;
        } else {
            return false;
        }
    }

}
