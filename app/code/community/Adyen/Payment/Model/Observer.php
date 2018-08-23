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
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */
class Adyen_Payment_Model_Observer
{

    /**
     * @event controller_action_predispatch
     * @param Varien_Event_Observer $observer
     */
    public function addMethodsToConfig(Varien_Event_Observer $observer = null)
    {
        if (Mage::app()->getStore()->isAdmin()) {
            $store = Mage::getSingleton('adminhtml/session_quote')->getStore();
        } else {
            $store = Mage::app()->getStore();
        }

        if(!Mage::app()->getStore()->isAdmin()){

            if ($observer->getControllerAction()->getFullActionName()=='onestepcheckout_index_index') {
                $lang_arr = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
                if (count($lang_arr)>0) {
                    $lang = $lang_arr['0'];
                    $lang =  strtolower($lang);


                    if(!Mage::getSingleton('customer/session')->getDefault()) {
                        $adapter = Mage::getSingleton('core/resource')->getConnection('core_write');
                        $lc_result = $adapter->query("select * from language_country where language_code='" . $lang . "'");
                        $lc = $lc_result->fetch();

                        $log_arr = array('id'=>$this->_getQuote()->getId(),'lan'=>$lang,'data'=>$lc);
                        Mage::log($log_arr,null,'lang.log');

                        if(isset($lc['currency_code'])&&!empty($lc['currency_code'])){
                            //Mage::app()->getStore()->setCurrentCurrencyCode($lc['currency_code']);
                            //Mage::getSingleton('customer/session')->setDefaultCurrency($lc['currency_code']);
                        }

                        if(isset($lc['country_code'])&&!empty($lc['country_code'])){
                            Mage::getSingleton('customer/session')->setDefaultCountry($lc['country_code']);
                        }

                        if(isset($lc['payment'])&&!empty($lc['payment'])){
                            Mage::getSingleton('customer/session')->setDefaultPayment($lc['payment']);
                        }

                        Mage::getSingleton('customer/session')->setDefault(true);
                    }else{
                        Mage::getSingleton('customer/session')->setDefaultCurrency(null);
                    }

                    if($currency = $observer->getControllerAction()->getRequest()->getParam('currency')){
                        Mage::app()->getStore()->setCurrentCurrencyCode($currency);
                        Mage::getSingleton('customer/session')->setDefaultCurrency($currency);
                    }
                }
            }
        }


        if (Mage::getStoreConfigFlag('payment/adyen_hpp/active', $store)) {
            // by default disable adyen_ideal only if IDeal is in directoryLookup result show this payment method
            $store->setConfig('payment/adyen_ideal/active', 0);

            try {
                $this->_addHppMethodsToConfig($store);
            } catch (Exception $e) {
                $store->setConfig('payment/adyen_hpp/active', 0);
                Adyen_Payment_Exception::logException($e);
            }
        }
    }


    /**
     * @param Mage_Core_Model_Store $store
     */
    protected function _addHppMethodsToConfig(Mage_Core_Model_Store $store)
    {
        Varien_Profiler::start(__CLASS__ . '::' . __FUNCTION__);

        if (!Mage::getStoreConfigFlag('payment/adyen_hpp/disable_hpptypes', $store)) {
            $sortOrder = Mage::getStoreConfig('payment/adyen_hpp/sort_order', $store);
            foreach ($this->_fetchHppMethods($store) as $methodCode => $methodData) {
                $this->createPaymentMethodFromHpp($methodCode, $methodData, $store, $sortOrder);
                $sortOrder += 10;
            }

            $store->setConfig('payment/adyen_hpp/active', 0);
        }

        Varien_Profiler::stop(__CLASS__ . '::' . __FUNCTION__);
    }

    /**
     * @param string $methodCode ideal,mc,etc.
     * @param array $methodData
     */
    public function createPaymentMethodFromHpp($methodCode, $methodData = array(), Mage_Core_Model_Store $store, $sortOrder)
    {
        $methodNewCode = 'adyen_hpp_' . $methodCode;
        if ($methodCode == 'ideal') {
            $methodNewCode = 'adyen_ideal';
            // enable adyen Ideal
            $store->setConfig('payment/adyen_ideal/active', 1);
        } else {
            $methodData = $methodData + Mage::getStoreConfig('payment/adyen_hpp', $store);
            $methodData['model'] = 'adyen/adyen_hpp';
        }

        foreach ($methodData as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $value = json_encode($value);
            }
            $store->setConfig('payment/' . $methodNewCode . '/' . $key, $value);
        }
        $store->setConfig('/payment/' . $methodNewCode . '/sort_order', $sortOrder);
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return array
     */
    protected function _fetchHppMethods(Mage_Core_Model_Store $store)
    {
        $adyenHelper = Mage::helper('adyen');

        $skinCode = $adyenHelper->getConfigData('skinCode', 'adyen_hpp', $store->getId());
        $merchantAccount = $adyenHelper->getConfigData('merchantAccount', null, $store->getId());

        if (!$skinCode || !$merchantAccount) {
            return array();
        }

        $adyFields = array(
            "paymentAmount" => (int)Mage::helper('adyen')->formatAmount($this->_getCurrentPaymentAmount(), $this->_getCurrentCurrencyCode()),
            "currencyCode" => $this->_getCurrentCurrencyCode(),
            "merchantReference" => "Get Payment methods",
            "skinCode" => $skinCode,
            "merchantAccount" => $merchantAccount,
            "sessionValidity" => date(
                DATE_ATOM,
                mktime(date("H") + 1, date("i"), date("s"), date("m"), date("j"), date("Y"))
            ),
            "countryCode" => $this->_getCurrentCountryCode($adyenHelper, $store),
            "shopperLocale" => $this->_getCurrentLocaleCode($adyenHelper, $store)
        );
        $responseData = $this->_getDirectoryLookupResponse($adyFields, $store);

        $paymentMethods = array();
        $ccTypes = array_keys(Mage::helper('adyen')->getCcTypesAltData());

        foreach ($responseData['paymentMethods'] as $paymentMethod) {
            $paymentMethod = $this->_fieldMapPaymentMethod($paymentMethod);
            $paymentMethodCode = $paymentMethod['brandCode'];

            //Skip open invoice methods if they are enabled
            if (Mage::getStoreConfigFlag('payment/adyen_openinvoice/active')
                && Mage::getStoreConfig('payment/adyen_openinvoice/openinvoicetypes') == $paymentMethodCode
            ) {
                continue;
            }

            if (Mage::getStoreConfigFlag('payment/adyen_cc/active')
                && in_array($paymentMethodCode, $ccTypes)
            ) {
                continue;
            }

            if (Mage::getStoreConfigFlag('payment/adyen_sepa/active')
                && in_array($paymentMethodCode, array('sepadirectdebit'))
            ) {
                continue;
            }

            if (Mage::getStoreConfigFlag('payment/adyen_multibanco/active')
                && in_array($paymentMethodCode, array('multibanco'))
            ) {
                continue;
            }

            if (Mage::getStoreConfigFlag('payment/adyen_cash/active')
                && in_array($paymentMethodCode, array('c_cash'))
            ) {
                continue;
            }

            if ($paymentMethodCode == 'entercash') {
                // if no issuers don't display it
                if (empty($paymentMethod['issuers'])) {
                    continue;
                }
            }

            unset($paymentMethod['brandCode']);
            $paymentMethods[$paymentMethodCode] = $paymentMethod;
        }

        return $paymentMethods;
    }


    /**
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }


    /**
     * @return string
     */
    protected function _getCurrentLocaleCode($adyenHelper, $store)
    {
        $localeCode = $adyenHelper->getConfigData('shopperlocale', 'adyen_abstract', $store->getId());
        if ($localeCode != "") {
            return $localeCode;
        }

        return Mage::app()->getLocale()->getLocaleCode();
    }


    /**
     * @return string
     */
    protected function _getCurrentCurrencyCode()
    {
        if(Mage::getSingleton('customer/session')->getDefaultCurrency()){
            return Mage::getSingleton('customer/session')->getDefaultCurrency();
        }
        return $this->_getQuote()->getQuoteCurrencyCode() ?: Mage::app()->getBaseCurrencyCode();
    }


    /**
     * @return string
     */
    protected function _getCurrentCountryCode($adyenHelper, $store)
    {
        // if fixed countryCode is setup in config use this
        $countryCode = $adyenHelper->getConfigData('countryCode', 'adyen_abstract', $store->getId());

        if ($countryCode != "") {
            return $countryCode;
        }

        //ling 登录状态只传地址ID
        if(Mage::helper('customer')->isLoggedIn()){
            $customerAddressId = Mage::app()->getRequest()->getPost('billing_address_id', false);
            if($customerAddressId){
                $billingAddress = Mage::getModel('customer/address')->load($customerAddressId);
                if(is_object($billingAddress)){
                    return $billingAddress->getCountryId();
                }
            }
        }

        $billingParams = Mage::app()->getRequest()->getParam('billing');
        if (isset($billingParams['country_id'])) {
            return $billingParams['country_id'];
        }

        if ($country = $this->_getQuote()->getBillingAddress()->getCountry()) {
            return $country;
        }

        if(Mage::getSingleton('customer/session')->getDefaultCountry()){
            return Mage::getSingleton('customer/session')->getDefaultCountry();
        }

        if (Mage::getStoreConfig('payment/account/merchant_country')) {
            return Mage::getStoreConfig('payment/account/merchant_country');
        }

        if (Mage::getStoreConfig('general/country/default')) {
            return Mage::getStoreConfig('general/country/default');
        }

        return null;
    }

    /**
     * @return bool|int
     */
    protected function _getCurrentPaymentAmount()
    {
        if (($grandTotal = $this->_getQuote()->getGrandTotal()) > 0) {
            return $grandTotal;
        }
        return 10;
    }


    /**
     * @param                       $requestParams
     * @param Mage_Core_Model_Store $store
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    protected function _getDirectoryLookupResponse($requestParams, Mage_Core_Model_Store $store)
    {
        $cacheKey = $this->_getCacheKeyForRequest($requestParams, $store);

        // Load response from cache.
        if ($responseData = Mage::app()->getCache()->load($cacheKey)) {
            return unserialize($responseData);
        }

        $this->_signRequestParams($requestParams, $store);

        $ch = curl_init();
        $url = Mage::helper('adyen')->getConfigDataDemoMode()
            ? "https://test.adyen.com/hpp/directory.shtml"
            : "https://live.adyen.com/hpp/directory.shtml";
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, count($requestParams));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestParams));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $results = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpStatus != 200) {
            Mage::throwException(
                Mage::helper('adyen')->__('HTTP Status code %s received, data %s', $httpStatus, $results)
            );
        }

        if ($results === false) {
            Mage::throwException(
                Mage::helper('adyen')->__('Got an empty response, status code %s', $httpStatus)
            );
        }

        $responseData = json_decode($results, true);
        if (!$responseData || !isset($responseData['paymentMethods'])) {
            Mage::throwException(Mage::helper('adyen')->__(
                'Did not receive JSON, could not retrieve payment methods, received %s request was: %s', $results, print_r($requestParams, true)
            ));
        }

        // Save response to cache.
        Mage::app()->getCache()->save(
            serialize($responseData),
            $cacheKey,
            array(Mage_Core_Model_Config::CACHE_TAG),
            60 * 60 * 6
        );

        return $responseData;
    }

    protected $_cacheParams = array(
        'currencyCode',
        'merchantReference',
        'skinCode',
        'merchantAccount',
        'countryCode',
        'shopperLocale',
    );


    /**
     * @param                       $requestParams
     * @param Mage_Core_Model_Store $store
     * @return string
     */
    protected function _getCacheKeyForRequest($requestParams, Mage_Core_Model_Store $store)
    {
        $cacheParams = array();
        $cacheParams['store'] = $store->getId();
        foreach ($this->_cacheParams as $paramKey) {
            if (isset($requestParams[$paramKey])) {
                $cacheParams[$paramKey] = $requestParams[$paramKey];
            }
        }

        return md5(implode('|', $cacheParams));
    }


    protected $_requiredHmacFields = array(
        'currencyCode',
        'merchantAccount',
        'paymentAmount',
        'skinCode',
        'merchantReference',
        'sessionValidity'
    );

    protected $_optionalHmacFields = array(
        'merchantReturnData',
        'shopperEmail',
        'shopperReference',
        'allowedMethods',
        'blockedMethods',
        'offset',
        'shopperStatement',
        'recurringContract',
        'billingAddressType',
        'deliveryAddressType'
    );


    /**
     * Communication between Adyen and the shop must be encoded with Hmac.
     * @param                       $fields
     * @param Mage_Core_Model_Store $store
     *
     * @throws Mage_Core_Exception
     * @throws Zend_Crypt_Hmac_Exception
     */
    protected function _signRequestParams(&$fields, Mage_Core_Model_Store $store)
    {
        unset($fields['merchantSig']);
        $hmacFields = $fields;

        foreach ($this->_requiredHmacFields as $requiredHmacField) {
            if (!isset($fields[$requiredHmacField])) {
                $fields[$requiredHmacField] = '';
            }
        }

        foreach ($fields as $field => $value) {
            if (!in_array($field, $this->_requiredHmacFields)
                && !in_array($field, $this->_optionalHmacFields)
            ) {
                unset($hmacFields[$field]);
            }
        }

        if (!$hmacKey = $this->_getHmacKey($store)) {
            Mage::throwException(Mage::helper('adyen')->__('You forgot to fill in HMAC key for Test or Live'));
        }

        // Sort the array by key using SORT_STRING order
        ksort($fields, SORT_STRING);

        // Generate the signing data string
        $signData = implode(":", array_map(array($this, 'escapeString'), array_merge(array_keys($fields), array_values($fields))));

        $signMac = Zend_Crypt_Hmac::compute(pack("H*", $hmacKey), 'sha256', $signData);
        $fields['merchantSig'] = base64_encode(pack('H*', $signMac));
    }

    /*
     * @desc The character escape function is called from the array_map function in _signRequestParams
     * $param $val
     * return string
     */
    protected function escapeString($val)
    {
        return str_replace(':', '\\:', str_replace('\\', '\\\\', $val));
    }


    /**
     * Get the Hmac key from the config
     * @param Mage_Core_Model_Store $store
     * @return string
     */
    protected function _getHmacKey(Mage_Core_Model_Store $store)
    {
        $adyenHelper = Mage::helper('adyen');
        switch ($adyenHelper->getConfigDataDemoMode()) {
            case true:
                $secretWord = trim($adyenHelper->getConfigData('secret_wordt', 'adyen_hpp'));
                break;
            default:
                $secretWord = trim($adyenHelper->getConfigData('secret_wordp', 'adyen_hpp'));
                break;
        }
        return $secretWord;
    }


    protected $_fieldMapPaymentMethod = array(
        'name' => 'title'
    );

    /**
     * @param $paymentMethod
     * @return mixed
     */
    protected function _fieldMapPaymentMethod($paymentMethod)
    {
        foreach ($this->_fieldMapPaymentMethod as $field => $newField) {
            if (isset($paymentMethod[$field])) {
                $paymentMethod[$newField] = $paymentMethod[$field];
                unset($paymentMethod[$field]);
            }
        }
        return $paymentMethod;
    }


    /**
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderPaymentCancel(Varien_Event_Observer $observer)
    {
        $adyenHelper = Mage::helper('adyen');

        $payment = $observer->getEvent()->getPayment();

        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();

        $autoRefund = $adyenHelper->getConfigData('autorefundoncancel', 'adyen_abstract', $order->getStoreId());

        if ($this->isPaymentMethodAdyen($order) && $autoRefund) {
            $pspReference = Mage::getModel('adyen/event')->getOriginalPspReference($order->getIncrementId());
            $payment->getMethodInstance()->sendCancelRequest($payment, $pspReference);
        }
    }

    /**
     * Determine if the payment method is Adyen
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function isPaymentMethodAdyen(Mage_Sales_Model_Order $order)
    {
        return strpos($order->getPayment()->getMethod(), 'adyen') !== false;
    }

    /**
     * Capture the invoice just before the shipment is created
     *
     * @param Varien_Event_Observer $observer
     * @return Adyen_Payment_Model_Observer $this
     * @throws Exception
     */
    public function captureInvoiceOnShipment(Varien_Event_Observer $observer)
    {
        /* @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = $observer->getShipment();

        /** @var Mage_Sales_Model_Order $order */
        $order = $shipment->getOrder();

        /** @var Adyen_Payment_Helper_Data $adyenHelper */
        $adyenHelper = Mage::helper('adyen');
        $storeId = $order->getStoreId();

        $captureOnShipment = $adyenHelper->getConfigData('capture_on_shipment', 'adyen_abstract', $storeId);
        $createPendingInvoice = $adyenHelper->getConfigData('create_pending_invoice', 'adyen_abstract', $storeId);

        // validate if payment method is adyen and if capture_on_shipment is enabled
        if ($this->isPaymentMethodAdyen($order) && $captureOnShipment) {
            if ($createPendingInvoice) {
                $transaction = Mage::getModel('core/resource_transaction');
                $transaction->addObject($order);

                foreach ($order->getInvoiceCollection() as $invoice) {
                    /* @var Mage_Sales_Model_Order_Invoice $invoice */
                    if (!$invoice->canCapture()) {
                        throw new Adyen_Payment_Exception($adyenHelper->__("Could not capture the invoice"));
                    }

                    $invoice->capture();
                    $invoice->setCreatedAt(now());
                    $transaction->addObject($invoice);
                }

                $order->setIsInProcess(true);
                $transaction->save();
            } else {
                // create an invoice and do a capture to adyen
                if ($order->canInvoice()) {
                    try {
                        /* @var Mage_Sales_Model_Order_Invoice $invoice */
                        $invoice = $order->prepareInvoice();
                        $invoice->getOrder()->setIsInProcess(true);

                        // set transaction id so you can do a online refund from credit memo
                        $invoice->setTransactionId(1);
                        $invoice->register()->capture();
                        $invoice->save();
                    } catch (Exception $e) {
                        Mage::logException($e);

                        throw new Adyen_Payment_Exception($adyenHelper->__("Could not capture the invoice"));
                    }

                    $invoiceAutoMail = (bool)$adyenHelper->getConfigData('send_invoice_update_mail', 'adyen_abstract', $storeId);
                    if ($invoiceAutoMail) {
                        $invoice->sendEmail();
                    }
                } else {
                    // If there is already an invoice created, continue shipment
                    if ($order->hasInvoices() == 0) {
                        throw new Adyen_Payment_Exception($adyenHelper->__("Could not create the invoice"));
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Set current invoice to payment when capturing.
     *
     * @param Varien_Event_Observer $observer
     * @return Adyen_Payment_Model_Observer $this
     */
    public function addCurrentInvoiceToPayment(Varien_Event_Observer $observer)
    {
        $invoice = $observer->getInvoice();
        $payment = $observer->getPayment();
        $payment->setCurrentInvoice($invoice);

        return $this;
    }
}
