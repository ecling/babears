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

class Adyen_Payment_CheckoutCashController extends Mage_Core_Controller_Front_Action {

    public function indexAction()
    {
        $customer = Mage::getSingleton('customer/session');

        // only proceed if customer is logged in
        if($customer->isLoggedIn()) {

            // get email
            $params = $this->getRequest()->getParams();
            $adyenPosEmail = isset($params['adyenPosEmail']) ? $params['adyenPosEmail'] : "";
            $quote = (Mage::getModel('checkout/type_onepage') !== false)? Mage::getModel('checkout/type_onepage')->getQuote(): Mage::getModel('checkout/session')->getQuote();

            // get customer object from session
            $customerObject = Mage::getModel('customer/customer')->load($customer->getId());

            // important update the shippingaddress and billingaddress this can be null sometimes.
            $quote->assignCustomerWithAddressChange($customerObject);

            // update email with customer Email
            if($adyenPosEmail != "") {
                $quote->setCustomerEmail($adyenPosEmail);
            }

            $shippingAddress = $quote->getShippingAddress();

            $shippingAddress->setCollectShippingRates(true)->collectShippingRates()
                ->setShippingMethod('freeshipping_freeshipping')
                ->setPaymentMethod('adyen_pos');

            $payment = $quote->getPayment();
            $payment->importData(array('method' => 'adyen_cash'));

            $quote->collectTotals()->save();
            $session = Mage::getSingleton('checkout/session');

            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();
            $order = $service->getOrder();

            $oderStatus = Mage::helper('adyen')->getOrderStatus();
            $order->setStatus($oderStatus);
            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
            $order->save();

            // redirect to success page
            $session->unsAdyenRealOrderId();
            $session->setLastSuccessQuoteId($session->getQuoteId());
            $session->getQuote()->setIsActive(false)->save();

            // needed for redirect through javascript (cashdrawer)
            $session->setLastQuoteId($session->getQuoteId());
            $session->setLastOrderId($order->getId());

            // redirect to page where cash drawer is open, do it in a seperate page bercause in checkout page it is not working looks like conflict with prototype
            $openCashDrawer = Mage::helper('adyen')->getConfigData("cash_drawer", "adyen_cash", null);
            if($openCashDrawer) {

                $cashDrawerIp = Mage::helper('adyen')->getConfigData("cash_drawer_printer_ip", "adyen_cash", $order->getStoreId());
                $cashDrawerPort = Mage::helper('adyen')->getConfigData("cash_drawer_printer_port", "adyen_cash", $order->getStoreId());
                $cashDrawerDeviceId = Mage::helper('adyen')->getConfigData("cash_drawer_printer_device_id", "adyen_cash", $order->getStoreId());

                if($cashDrawerIp != '' && $cashDrawerPort != '' && $cashDrawerDeviceId != '') {

                    $html = '<html><head><link rel="stylesheet" type="text/css" href="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'frontend/base/default/css/adyenstyle.css"><script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js" ></script>';

                    // for cash add epson libary to open the cash drawer
                    $jsPath = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS);

                    $html .= '<script src="'.$jsPath.'adyen/payment/epos-device-2.6.0.js"></script>';
                    $html .= '</head><body class="redirect-body-adyen">';
                    $html.= '
                            <script type="text/javascript">
                                var ipAddress = "'.$cashDrawerIp.'";
                                var port = "'.$cashDrawerPort.'";
                                var deviceID = "'.$cashDrawerDeviceId.'";
                                var ePosDev = new epson.ePOSDevice();
                                ePosDev.connect(ipAddress, port, Callback_connect);

                                function Callback_connect(data) {
                                    if (data == "OK" || data == "SSL_CONNECT_OK") {
                                        var options = "{}";
                                        ePosDev.createDevice(deviceID, ePosDev.DEVICE_TYPE_PRINTER, options, callbackCreateDevice_printer);
                                    } else {
                                        alert("connected to ePOS Device Service Interface is failed. [" + data + "]");
                                    }
                                }

                                function callbackCreateDevice_printer(data, code) {
                                    var print = data;
                                    var drawer = "{}";
                                    var time = print.PULSE_100
                                    print.addPulse();
                                    print.send();
                                    window.location = "'. Mage::getUrl('checkout/onepage/success') .'";
                                }
                            </script>
                    ';

                    $html.= '</body></html>';

                    $this->getResponse()->setBody($html);
                } else {
                    Mage::throwException(
                        Mage::helper('adyen')->__('You did not fill in all the fields (ip,port,device id) to use Cash Drawer support')
                    );
                }
            } else {
                $this->_redirect('checkout/onepage/success');
            }
        } else {
            Mage::throwException(
                Mage::helper('adyen')->__('Customer is not logged in.')
            );
        }
    }
}
