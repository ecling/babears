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
class Adyen_Payment_Block_Redirect extends Mage_Core_Block_Abstract {

    /**
     * Collected debug information
     *
     * @var array
     */
    protected $_debugData = array();

    /**
     * Zend_Log debug level
     * @var unknown_type
     */
    const DEBUG_LEVEL = 7;

    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getOrder() {
        if ($this->getOrder()) {
            return $this->getOrder();
        } else {
            // log the exception
            Mage::log("Redirect exception could not load the order:", Zend_Log::DEBUG, "adyen_notification.log", true);
            return null;
        }
    }

    protected function _toHtml() {

        $order = $this->_getOrder();
        $paymentObject = $order->getPayment();
        $payment = $order->getPayment()->getMethodInstance();

        $html = '<html><head><link rel="stylesheet" type="text/css" href="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'/frontend/base/default/css/adyenstyle.css"><script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js" ></script>';

        // for cash add epson libary to open the cash drawer
        $cashDrawer = $this->_getConfigData("cash_drawer", "adyen_pos", null);
        if($payment->getCode() == "adyen_hpp_c_cash" && $cashDrawer) {
            $jsPath = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS);
            $html .= '<script src="'.$jsPath.'adyen/payment/epos-device-2.6.0.js"></script>';
        }

        //ga and facebook pixel

        $content_ids = array();
        foreach($order->getAllVisibleItems() as $_item){
            $content_ids[] = '"'.$_item->getProductId().'"';
        }
        $content_ids = implode(',',$content_ids);

        $html .= '
                <script>
                !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                n.push=n;n.loaded=!0;n.version=\'2.0\';n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
                document,\'script\',\'https://connect.facebook.net/en_US/fbevents.js\');
                fbq(\'init\', \'705787842925898\'); // Insert your pixel ID here.
                fbq(\'track\', \'PageView\');
                fbq(\'track\', \'Purchase\', {
                    source: \'magento\',
                    version: "1.9.2.2",
                    pluginVersion: "2.1.14",
                    content_type: "product", 
                    content_ids: ['.$content_ids.'],
                    value: \''. $order->getBaseGrandTotal() .'\',
                    currency: \''. Mage::app()->getStore()->getCurrentCurrencyCode().'\'
                  });
                </script>
                <noscript><img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id=705787842925898&ev=PageView&noscript=1"
                /></noscript>
                ';


        $html .= '
                <script type="text/javascript">
                (function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){
                    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
                })(window,document,\'script\',\'//www.google-analytics.com/analytics.js\',\'ga\');
        
                ga(\'create\', \'UA-74088050-1\', \'www.bellecat.com\');
                ga(\'set\', \'anonymizeIp\', false);
                ga(\'send\', \'pageview\');
                </script>
                ';

        $html .= '
            <script type="text/javascript">
            ga(\'require\', \'ecommerce\', \'ecommerce.js\');
            ga(\'ecommerce:addTransaction\', { \'id\': \''. $order->getData('entity_id').'\', \'affiliation\': \''.Mage::app()->getStore()->getName().'\', \'revenue\': \''.$order->getGrandTotal().'\', \'shipping\': \''. $order->getShippingInclTax() .'\', \'tax\': \''.  $order->getTaxAmount().'\', \'currency\': \''.$order->getOrderCurrencyCode().'\'});
            ';
            foreach($order->getAllVisibleItems() as $_item){
            if($_item->getParentItem()) continue;
                $html .= 'ga(\'ecommerce:addItem\', {\'id\': \''.$order->getData('entity_id') .'\', \'name\': \''. str_replace("'","\'", $_item->getName()).'\', \'sku\': \''. $_item->getSku().'\', \'price\': \''. $_item->getPrice() .'\', \'quantity\': \''. (int) $_item->getQtyOrdered() .'\'});';
            }
       $html .=  'ga(\'ecommerce:send\');
          </script>
        ';

        $html .= '</head><body class="redirect-body-adyen">';


        // if pos payment redirect to app
        if($payment->getCode() == "adyen_pos") {

            $adyFields = $payment->getFormFields();
            // use the secure url (if not secure this will be filled in with http://
            $url = urlencode(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true)."adyen/process/successPos");

            // detect ios or android
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            $iPod    = stripos($userAgent,"iPod");
            $iPhone  = stripos($userAgent,"iPhone");
            $iPad    = stripos($userAgent,"iPad");
            $Android = stripos($userAgent,"Android");
            $webOS   = stripos($userAgent,"webOS");

            // extra parameters so that you alway's return these paramters from the application
            $extra_paramaters = urlencode("/?originalCustomCurrency=".$adyFields['currencyCode']."&originalCustomAmount=".$adyFields['paymentAmount']. "&originalCustomMerchantReference=".$adyFields['merchantReference'] . "&originalCustomSessionId=".session_id());

            // add recurring before the callback url
            if(empty($adyFields['recurringContract'])) {
                $recurring_parameters = "";
            } else {
                $recurring_parameters = "&recurringContract=".urlencode($adyFields['recurringContract'])."&shopperReference=".urlencode($adyFields['shopperReference']). "&shopperEmail=".urlencode($adyFields['shopperEmail']);
            }



            $addReceiptOrderLines = $this->_getConfigData("add_receipt_order_lines", "adyen_pos", null);

            $receiptOrderLines = "";
            if($addReceiptOrderLines) {
                $orderLines = base64_encode($this->getReceiptOrderLines($this->_getOrder()));
                $receiptOrderLines = "&receiptOrderLines=" . urlencode($orderLines);
            }

            // important url must be the latest parameter before extra parameters! otherwise extra parameters won't return in return url
            $launchlink = "adyen://payment?sessionId=".session_id()."&amount=".$adyFields['paymentAmount']."&currency=".$adyFields['currencyCode']."&merchantReference=".$adyFields['merchantReference']. $recurring_parameters . $receiptOrderLines .  "&callback=".$url . $extra_paramaters;

            // log the launchlink
            $this->_debugData['LaunchLink'] = $launchlink;
            $storeId = $order->getStoreId();
            $this->_debug($storeId);

            // call app directly without HPP
            $html .= "<div id=\"pos-redirect-page\">
    					<div class=\"logo\"></div>
    					<div class=\"grey-header\">
    						<h1>{$this->__('POS Payment')}</h1>
    					</div>
    					<div class=\"amount-box\">".
                $adyFields['paymentAmountGrandTotal'] .
                "<a id=\"launchlink\" href=\"".$launchlink ."\" >{$this->__('Payment')}</a> ".
                "<span id=\"adyen-redirect-text\">{$this->__('If you stuck on this page please press the payment button')}</span></div>";

            $html .= '<script type="text/javascript">
    				//<![CDATA[
    				';

            if($iPod || $iPhone || $iPad) {
                $html .= 'document.getElementById(\'launchlink\').click();';
            } else {
                // android
                $html .= 'url = document.getElementById(\'launchlink\').href;';
                $html .= 'window.location = url;';
            }

            $html .= '
                        //]]>
                        </script>
                    </div>';
        } else {

            // do not use Magento form because this generate a form_key input field
            $html .= '<form name="adyenForm" id="' . $payment->getCode() . '" action="' . $payment->getFormUrl() . '" method="post">';

            foreach ($payment->getFormFields() as $field => $value) {
                $html .= '<input type="hidden" name="' .htmlspecialchars($field,   ENT_COMPAT | ENT_HTML401 ,'UTF-8').
                    '" value="' .htmlspecialchars($value, ENT_COMPAT | ENT_HTML401 ,'UTF-8') . '" />';
            }

            $html .= '</form>';

            if($payment->getCode() == "adyen_hpp_c_cash" && $cashDrawer) {

                $cashDrawerIp = $this->_getConfigData("cash_drawer_printer_ip", "adyen_pos", null);
                $cashDrawerPort = $this->_getConfigData("cash_drawer_printer_port", "adyen_pos", null);
                $cashDrawerDeviceId = $this->_getConfigData("cash_drawer_printer_device_id", "adyen_pos", null);

                if($cashDrawerIp != '' && $cashDrawerPort != '' && $cashDrawerDeviceId != '') {
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
                                    document.getElementById("'.$payment->getCode().'").submit();
                                }
                            </script>
                    ';
                } else {
                    Mage::log("You did not fill in all the fields (ip,port,device id) to use Cash Drawer support:", Zend_Log::DEBUG, "adyen_notification.log", true);
                }
            } else {
                $html.= '<script type="text/javascript">document.getElementById("'.$payment->getCode().'").submit();</script>';
            }
        }
        $html.= '</body></html>';

        // log the actual HTML
        Mage::log($html, self::DEBUG_LEVEL, 'adyen_http-request-form.log');

        return $html;
    }


    /**
     * Log debug data to file
     *
     * @param mixed $debugData
     */
    protected function _debug($storeId)
    {
        if ($this->_getConfigData('debug', 'adyen_abstract', $storeId)) {
            $file = 'adyen_request_pos.log';
            Mage::getModel('core/log_adapter', $file)->log($this->_debugData);
        }
    }

    private function getReceiptOrderLines($order) {

        $myReceiptOrderLines = "";

        // temp
        $currency = $order->getOrderCurrencyCode();
        $formattedAmountValue = Mage::helper('core')->formatPrice($order->getGrandTotal(), false);

        $formattedAmountValue = Mage::getModel('directory/currency')->format(
            $order->getGrandTotal(),
            array('display'=>Zend_Currency::NO_SYMBOL),
            false
        );

        $taxAmount = Mage::helper('checkout')->getQuote()->getShippingAddress()->getData('tax_amount');
        $formattedTaxAmount = Mage::getModel('directory/currency')->format(
            $taxAmount,
            array('display'=>Zend_Currency::NO_SYMBOL),
            false
        );

        $paymentAmount = "1000";

        $myReceiptOrderLines .= "---||C\n".
            "====== YOUR ORDER DETAILS ======||CB\n".
            "---||C\n".
            " No. Description |Piece  Subtotal|\n";

        foreach ($order->getItemsCollection() as $item) {
            //skip dummies
            if ($item->isDummy()) continue;
            $singlePriceFormat = Mage::getModel('directory/currency')->format(
                $item->getPriceInclTax(),
                array('display'=>Zend_Currency::NO_SYMBOL),
                false
            );

            $itemAmount = $item->getPriceInclTax() * (int) $item->getQtyOrdered();
            $itemAmountFormat = Mage::getModel('directory/currency')->format(
                $itemAmount,
                array('display'=>Zend_Currency::NO_SYMBOL),
                false
            );
            $myReceiptOrderLines .= "  " . (int) $item->getQtyOrdered() . "  " . trim(substr($item->getName(),0, 25)) . "| " . $currency . " " . $singlePriceFormat . "  " . $currency . " " . $itemAmountFormat . "|\n";
        }

        //discount cost
        if($order->getDiscountAmount() > 0 || $order->getDiscountAmount() < 0)
        {
            $discountAmountFormat = Mage::getModel('directory/currency')->format(
                $order->getDiscountAmount(),
                array('display'=>Zend_Currency::NO_SYMBOL),
                false
            );
            $myReceiptOrderLines .= "  " . 1 . " " . $this->__('Total Discount') . "| " . $currency . " " . $discountAmountFormat ."|\n";
        }

        //shipping cost
        if($order->getShippingAmount() > 0 || $order->getShippingTaxAmount() > 0)
        {
            $shippingAmountFormat = Mage::getModel('directory/currency')->format(
                $order->getShippingAmount(),
                array('display'=>Zend_Currency::NO_SYMBOL),
                false
            );
            $myReceiptOrderLines .= "  " . 1 . " " . $order->getShippingDescription() . "| " . $currency . " " . $shippingAmountFormat ."|\n";

        }

        if($order->getPaymentFeeAmount() > 0) {
            $paymentFeeAmount =  Mage::getModel('directory/currency')->format(
                $order->getPaymentFeeAmount(),
                array('display'=>Zend_Currency::NO_SYMBOL),
                false
            );
            $myReceiptOrderLines .= "  " . 1 . " " . $this->__('Payment Fee') . "| " . $currency . " " . $paymentFeeAmount ."|\n";

        }

        $myReceiptOrderLines .=    "|--------|\n".
            "|Order Total:  ".$currency." ".$formattedAmountValue."|B\n".
            "|Tax:  ".$currency." ".$formattedTaxAmount."|B\n".
            "||C\n";

        //Cool new header for card details section! Default location is After Header so simply add to Order Details as separator
        $myReceiptOrderLines .= "---||C\n".
            "====== YOUR PAYMENT DETAILS ======||CB\n".
            "---||C\n";


        return $myReceiptOrderLines;

    }

    /**
     * @param $code
     * @param null $paymentMethodCode
     * @param null $storeId
     * @return mixed
     */
    protected function _getConfigData($code, $paymentMethodCode = null, $storeId = null)
    {
        return Mage::helper('adyen')->getConfigData($code, $paymentMethodCode, $storeId);
    }

}
