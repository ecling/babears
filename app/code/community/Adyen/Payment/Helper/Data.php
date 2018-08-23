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
class Adyen_Payment_Helper_Data extends Mage_Payment_Helper_Data
{

    const KLARNA = "klarna";
    const RATEPAY = "ratepay";
    const AFTERPAY = "afterpay";

    /**
     * @return array
     */
    public function getCcTypes()
    {
        $_types = Mage::getConfig()->getNode('default/adyen/payment/cctypes')->asArray();
        uasort($_types, array('Mage_Payment_Model_Config', 'compareCcTypes'));
        $types = array();
        foreach ($_types as $data) {
            if (! $data['is_checkout']) {
                continue;
            }
            $types[$data['code']] = $data;
        }
        return $types;
    }


    /**
     *
     */
    public function getCcTypesAltData()
    {
        $_types = Mage::getConfig()->getNode('default/adyen/payment/cctypes')->asArray();
        uasort($_types, array('Mage_Payment_Model_Config', 'compareCcTypes'));
        $types = array();
        foreach ($_types as $data) {
            $types[$data['code_alt']] = $data;
        }
        return $types;
    }


    /**
     * @return array
     */
    public function getBoletoTypes()
    {
        $_types = Mage::getConfig()->getNode('default/adyen/payment/boletotypes')->asArray();
        $types = array();
        foreach ($_types as $data) {
            $types[$data['code']] = $data['name'];
        }
        return $types;
    }


    /**
     * @return array
     */
    public function getOpenInvoiceTypes()
    {
        $_types = Mage::getConfig()->getNode('default/adyen/payment/openinvoicetypes')->asArray();
        $types = array();
        foreach ($_types as $data) {
            $types[$data['code']] = $data['name'];
        }
        return $types;
    }


    /**
     * @return array
     */
    public function getRecurringTypes()
    {
        $_types = Mage::getConfig()->getNode('default/adyen/payment/recurringtypes')->asArray();
        $types = array();
        foreach ($_types as $data) {
            $types[$data['code']] = $data['name'];
        }
        return $types;
    }


    /**
     * @return string
     */
    public function getExtensionVersion()
    {
        return (string) Mage::getConfig()->getModuleConfig('Adyen_Payment')->version;
    }


    /**
     * @return bool|int
     */
    public function hasEnableScanner()
    {
        if(Mage::getStoreConfig('payment/adyen_pos/active')) {
            return (int) Mage::getStoreConfig('payment/adyen_pos/enable_scanner');
        }
        return false;
    }


    /**
     * @return int
     */
    public function hasAutoSubmitScanner()
    {
        return (int) Mage::getStoreConfig('payment/adyen_pos/auto_submit_scanner');
    }


    /**
     * @return bool|int
     */
    public function hasExpressCheckout()
    {
        if(Mage::getStoreConfig('payment/adyen_pos/active')) {
            // check if metmethod is available
            $methodModel = Mage::getModel('adyen/adyen_pos');
            if ($methodModel) {
                if($methodModel->isAvailable()) {
                    return (int) Mage::getStoreConfig('payment/adyen_pos/express_checkout');
                }
            }
        }
        return false;
    }

    /**
     * @return bool|int
     */
    public function hasCashExpressCheckout()
    {
        if(Mage::getStoreConfig('payment/adyen_cash/active'))
        {
            // check if metmethod is available
            $methodModel = Mage::getModel('adyen/adyen_cash');
            if ($methodModel) {
                if($methodModel->isAvailable()) {
                    return (int) Mage::getStoreConfig('payment/adyen_cash/cash_express_checkout');
                }
            }
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getOrderStatus()
    {
        return Mage::getStoreConfig('payment/adyen_abstract/order_status');
    }

    /**
     * Return the formatted currency. Adyen accepts the currency in multiple formats.
     * @param $amount
     * @param $currency
     *
     * @return int
     */
    public function formatAmount($amount, $currency)
    {
        switch($currency) {
            case "JPY":
            case "IDR":
            case "KRW":
            case "BYR":
            case "VND":
            case "CVE":
            case "DJF":
            case "GNF":
            case "PYG":
            case "RWF":
            case "UGX":
            case "VUV":
            case "XAF":
            case "XOF":
            case "XPF":
            case "GHC":
            case "KMF":
                $format = 0;
                break;
            case "MRO":
                $format = 1;
                break;
            case "BHD":
            case "JOD":
            case "KWD":
            case "OMR":
            case "LYD":
            case "TND":
                $format = 3;
                break;
            default:
                $format = 2;
                break;
        }

        return (int)number_format($amount, $format, '', '');
    }

    public function originalAmount($amount, $currency)
    {
        // check the format
        switch($currency) {
            case "JPY":
            case "IDR":
            case "KRW":
            case "BYR":
            case "VND":
            case "CVE":
            case "DJF":
            case "GNF":
            case "PYG":
            case "RWF":
            case "UGX":
            case "VUV":
            case "XAF":
            case "XOF":
            case "XPF":
            case "GHC":
            case "KMF":
                $format = 1;
                break;
            case "MRO":
                $format = 10;
                break;
            case "BHD":
            case "JOD":
            case "KWD":
            case "OMR":
            case "LYD":
            case "TND":
                $format = 1000;
                break;
            default:
                $format = 100;
                break;
        }

        return ($amount / $format);
    }

    /**
     * Creditcard type that is selected is different from creditcard type that we get back from the request this
     * function get the magento creditcard type this is needed for getting settings like installments
     * @param $ccType
     * @return mixed
     */
    public function getMagentoCreditCartType($ccType)
    {

        $ccTypesMapper = Mage::helper('adyen')->getCcTypesAltData();

        if(isset($ccTypesMapper[$ccType])) {
            $ccType = $ccTypesMapper[$ccType]['code'];
        }

        return $ccType;
    }

    /**
     * Used via Payment method.Notice via configuration ofcourse Y or N
     * @return boolean true on demo, else false
     */
    public function getConfigDataDemoMode($storeId = null)
    {
        if ($this->getConfigData('demoMode', null, $storeId) == 'Y') {
            return true;
        }
        return false;
    }


    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getConfigDataWsUserName($storeId = null)
    {
        if ($this->getConfigDataDemoMode($storeId)) {
            return $this->getConfigData('ws_username_test', null, $storeId);
        }
        return $this->getConfigData('ws_username_live', null, $storeId);
    }


    /**
     * @param null $storeId
     *
     * @return string
     */
    public function getConfigDataWsPassword($storeId = null)
    {
        if ($this->getConfigDataDemoMode($storeId)) {
            return Mage::helper('core')->decrypt($this->getConfigData('ws_password_test', null, $storeId));
        }
        return Mage::helper('core')->decrypt($this->getConfigData('ws_password_live', null, $storeId));
    }


    /**
     * @param      $code
     * @param null $paymentMethodCode
     * @param null $storeId
     * @deprecated please use getConfigData
     * @return mixed
     */
    public function _getConfigData($code, $paymentMethodCode = null, $storeId = null)
    {
        return $this->getConfigData($code, $paymentMethodCode, $storeId);
    }


    /**
     * @desc    Give Default settings
     * @example $this->_getConfigData('demoMode','adyen_abstract')
     * @since   0.0.2
     *
     * @param string $code
     *
     * @return mixed
     */
    public function getConfigData($code, $paymentMethodCode = null, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = Mage::app()->getStore()->getStoreId();
        }
        if (empty($paymentMethodCode)) {
            return trim(Mage::getStoreConfig("payment/adyen_abstract/$code", $storeId));
        }
        return trim(Mage::getStoreConfig("payment/$paymentMethodCode/$code", $storeId));
    }


    /**
     * Get the client ip address
     * @return string
     */
    public function getClientIp()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif(isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        }elseif(isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif(isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif(isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = '';
        }

        return $ipaddress;
    }


    /**
     * Is the IPv4/v6 IP address in the given range
     * @param $ip
     * @param $from
     * @param $to
     *
     * @return bool
     */
    public function ipInRange($ip, $from, $to)
    {
        $ip = $this->getIpNumberFromAddress($ip);
        $from = $this->getIpNumberFromAddress($from);
        $to = $this->getIpNumberFromAddress($to);

        return (!$ip || !$from || !$to) ? false : ($ip <= $to && $from <= $ip);
    }

    /**
     * Converts any given IPv4/v6 address to a string representation of it's 32/128bit integer
     * which may be used for comparison
     *
     * @param string $address
     * @return string|bool
     */
    public function getIpNumberFromAddress($address)
    {
        // Unrecognised addresses cause PHP warnings, silence the warning and return a bool instead
        $pton = @inet_pton($address);
        if (!$pton) {
            return false;
        }

        $number = '';
        foreach (unpack('C*', $pton) as $byte) {
            $number .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }

        return base_convert(ltrim($number, '0'), 2, 10);
    }


    /**
     * Street format
     * @param type $address
     * @return Varien_Object
     */
    public function getStreet($address, $klarna = false)
    {
        if (empty($address)) return false;
        $street = $this->formatStreet($address->getStreet(), $klarna);
        $streetName = $street['0'];
        unset($street['0']);
//        $streetNr = implode('',$street);
        $streetNr = implode(' ',$street);

        return new Varien_Object(array('name' => trim($streetName), 'house_number' => trim($streetNr)));
    }

    /**
     * Fix this one string street + number
     * @example street + number
     * @param type $street
     * @return type $street
     */
    protected function formatStreet($street, $klarna)
    {
        $formatStreetOnMultiStreetLines = false;

        /*
         * If ignore second line steet is enabled for klarna only look at the first addressfield
         * and try to substract housenumber
         */
        if($klarna) {
            if($this->getConfigData('ignore_second_address_field','adyen_openinvoice')) {
                $formatStreetOnMultiStreetLines = true;
            }
        }

        if (!$formatStreetOnMultiStreetLines && count($street) != 1) {
            return $street;
        }

        preg_match('/\s(\d+.*)$/i', $street['0'], $houseNumber, PREG_OFFSET_CAPTURE);
        if(!empty($houseNumber['0'])) {
            $_houseNumber = trim($houseNumber['0']['0']);
            $position = $houseNumber['0']['1'];
            $streeName = trim(substr($street['0'], 0, $position));
            $street = array($streeName,$_houseNumber);
        }
        return $street;
    }

    /**
     * @desc Tax Percentage needs to be in minor units for Adyen
     * @param $taxPercent
     * @return int
     */
    public function getMinorUnitTaxPercent($taxPercent)
    {
        $taxPercent = $taxPercent * 100;
        return (int)$taxPercent;
    }

    /**
     * @desc Calculate the tax rate for tax class based on order details
     * @param $order
     * @param $taxClass
     * @return mixed
     */
    public function getTaxRate($order, $taxClass)
    {
        // load the customer so we can retrieve the correct tax class id
        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
        $calculation = Mage::getModel('tax/calculation');

        $request = $calculation->getRateRequest(
            $order->getShippingAddress(),
            $order->getBillingAddress(),
            $customer->getTaxClassId(),
            $order->getStore()
        );
        return $calculation->getRate($request->setProductClassId($taxClass));
    }
    
    /**
     * @param null $storeId
     * @return mixed
     */
    public function getApplePayMerchantIdentifier($storeId = null)
    {
        if ($this->getConfigDataDemoMode($storeId)) {
            return $this->getConfigData('merchant_identifier_test', 'adyen_apple_pay', $storeId);
        }
        return $this->getConfigData('merchant_identifier_live', 'adyen_apple_pay', $storeId);
    }
    
    /**
     * @param null $storeId
     * @return mixed
     */
    public function getApplePayFullPathLocationPEMFile($storeId = null)
    {
        if ($this->getConfigDataDemoMode($storeId)) {
            return $this->getConfigData('full_path_location_pem_file_test', 'adyen_apple_pay', $storeId);
        }
        return $this->getConfigData('full_path_location_pem_file_live', 'adyen_apple_pay', $storeId);
    }


    /**
     * Identifiy if payment method is an openinvoice payment method
     *
     * @param $paymentMethod
     * @return bool
     */
    public function isOpenInvoice($paymentMethod)
    {
        if( $this->isKlarna($paymentMethod)  ||
            strcmp($paymentMethod, self::RATEPAY) === 0 ||
            $this->isAfterPay($paymentMethod))
        {
            return true;
        }
        return false;
    }

    /**
     * Identifiy if paymentMethod is afterpay
     *
     * @param $paymentMethod
     * @return bool
     */
    public function isAfterPay($paymentMethod)
    {
        if(strcmp(substr($paymentMethod, 0, 8), self::AFTERPAY) === 0) {
            return true;
        }
        return false;
    }

    /**
     * @param $paymentMethod
     * @return bool
     */
    public function isKlarna($paymentMethod)
    {
        if(strcmp(substr($paymentMethod, 0, 6), self::KLARNA) === 0) {
            return true;
        }
        return false;
    }

    /**
     * Defines if it need to use the admin session or checkout session
     *
     * @return mixed
     */
    public function getSession()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $session = Mage::getSingleton('adminhtml/session_quote');
        } else {
            $session = Mage::getSingleton('checkout/session');
        }
        return $session;
    }


    public function getUnprocessedNotifications()
    {
        // get collection of unprocessed notifications
        $collection = Mage::getModel('adyen/event_queue')->getCollection()
            ->addFieldToFilter('created_at', array(
                'to' => strtotime('-10 minutes', time()),
                'datetime' => true));

        return $collection->getSize();
    }
}
