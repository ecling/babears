<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Hosted
 */
class CheckoutApi_ChargePayment_Model_Hosted extends Mage_Payment_Model_Method_Abstract
{
    const CARD_FORM_MODE    = 'cardTokenisation';
    const PAYMENT_MODE      = 'cards';
    const RENDER_MODE       = 2;
    const RENDER_NAMESPACE  = 'CheckoutIntegration';


    const PAYMENT_MODE_MIXED            = 'mixed';
    const PAYMENT_MODE_CARD             = 'cards';
    const PAYMENT_MODE_LOCAL_PAYMENT    = 'localpayments';

    protected $_formBlockType = 'chargepayment/form_checkoutApiHosted';
    protected $_infoBlockType = 'chargepayment/info_checkoutApiHosted';

    protected $_code = CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_HOSTED;

    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                 = true;
    protected $_isInitializeNeeded      = true;

    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;


    public function assignData($data){
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info   = $this->getInfoInstance()
            ->setCheckoutApiCardId('')
            ->setPoNumber($data->getSaveCardCheck());

        $result = $this->_getSavedCartDataFromPost($data);

        if (!empty($result)) {
            $cardId = $result['checkout_api_card_id'];
            Mage::getSingleton('core/session')->setHostedCardId($cardId);
        }

        return $this;
    }

    /**
     * Redirect URL after order place
     *
     * @return mixed
     */
    public function getOrderPlaceRedirectUrl() {
        $session        = Mage::getSingleton('chargepayment/session_quote');
        $redirectUrl    = $session->getHostedPaymentRedirect();

        $getParam = Mage::app()->getRequest()->getParams();

        if(isset($getParam['payment']['customer_card'])){
            $isNewCard = $getParam['payment']['customer_card'];
        } else {
            $isNewCard = 'new_card';
        }

        $redirect = Mage::getModel('core/url')->getUrl('chargepayment/api/hosted/');

        if(is_null($isNewCard)){
            return $redirectUrl;
        }

        if ($redirectUrl && $isNewCard == 'new_card') {
            Mage::helper('chargepayment')->setOrderPendingPayment();
            return $redirectUrl;
        }

        return $redirect;
    }

    protected function _getSavedCartDataFromPost($data) {

        $savedCard  = $data->getCustomerCard();


        /* If non saved card */
        if (empty($savedCard) || $savedCard === 'new_card') {
            return array();
        }

        $customerId = $this->getCustomerId();

        /* If user not logged */
        if (empty($customerId)) {
            return array();
        }

        $cardModel  = Mage::getModel('chargepayment/customerCard');
        $collection = $cardModel->getCustomerCardList($customerId);

        /* If user not have saved cards */
        if (!$collection->count()) {
            return array();
        }

        $trueData       = false;
        $customerCard   = array();

        foreach($collection as $entity) {
            $secret = $cardModel->getCardSecret($entity->getId(), $entity->getCardNumber(), $entity->getCardType());

            if ($savedCard === $secret) {
                $trueData = true;
                $customerCard = $entity;
                break;
            }
        }

        if (!$trueData) {
            Mage::throwException(Mage::helper('chargepayment')->__('Please check your card data.'));
        }

        $result['checkout_api_card_id'] = $customerCard->getCardId();

        return $result;
    }

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param object $stateObject
     * @return $this
     */
    public function initialize($paymentAction, $stateObject) {
        $payment    = $this->getInfoInstance();
        $order      = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $payment->setAmountAuthorized($order->getTotalDue());
        $payment->setAmount($order->getTotalDue());
        $payment->setBaseAmountAuthorized($order->getBaseTotalDue());

        switch ($paymentAction) {
            case self::ACTION_AUTHORIZE_CAPTURE:
                $this->_setPaymentToken($payment, true);
                break;
            default:
                $this->_setPaymentToken($payment, false);
                break;
        }

        $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);

        return $this;
    }

    /**
     * Create Payment Token
     *
     * @return array
     */
    public function getPaymentToken() {
        $Api                = CheckoutApi_Api::getApi(array('mode' => $this->getEndpointMode()));
        $isCurrentCurrency  = $this->getIsUseCurrentCurrency();
        $price              = $isCurrentCurrency ? $this->_getQuote()->getGrandTotal() : $this->_getQuote()->getBaseGrandTotal();
        $priceCode          = $isCurrentCurrency ? Mage::app()->getStore()->getCurrentCurrencyCode() : Mage::app()->getStore()->getBaseCurrencyCode();

        // does not create charge on checkout.com if amount is 0
        if (empty($price)) {
            return array();
        }

        $amount     = $Api->valueToDecimal($price, $priceCode);
        $config     = $this->_getCharge($amount);

        $paymentTokenCharge = $Api->getPaymentToken($config);

        if ($Api->getExceptionState()->hasError()) {
            Mage::log($Api->getExceptionState()->getErrorMessage(), null, $this->_code.'.log');
            Mage::log($Api->getExceptionState(), null, $this->_code.'.log');

            return array(
                'success' => false,
                'token'   => '',
                'message' => $Api->getExceptionState()->getErrorMessage()
            );
        }

        $paymentTokenReturn     = array(
            'success' => false,
            'token'   => '',
            'message' => ''
        );

        if($paymentTokenCharge->isValid()){
            $paymentToken                   = $paymentTokenCharge->getId();
            $paymentTokenReturn['token']    = $paymentToken ;
            $paymentTokenReturn['success']  = true;

            $paymentTokenReturn['customerEmail']    = $config['postedParam']['email'];
            $paymentTokenReturn['customerName']     = $config['postedParam']['customerName'];
            $paymentTokenReturn['value']            = $amount;
            $paymentTokenReturn['currency']         = $priceCode;

            Mage::getSingleton('chargepayment/session_quote')->addPaymentToken($paymentToken);
        }else {
            if($paymentTokenCharge->getEventId()) {
                $eventCode = $paymentTokenCharge->getEventId();
            }else {
                $eventCode = $paymentTokenCharge->getErrorCode();
            }
            $paymentTokenReturn['message'] = Mage::helper('payment')->__( $paymentTokenCharge->getExceptionState()->getErrorMessage().
                ' ( '.$eventCode.')');

            Mage::logException($paymentTokenReturn['message']);
        }

        return $paymentTokenReturn;
    }

    /**
     * Set Payment Token to session
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param $isAutoCapture
     * @return $this
     * @throws Mage_Core_Exception
     */
    protected function _setPaymentToken(Mage_Sales_Model_Order_Payment $payment, $isAutoCapture) {
        $Api                = CheckoutApi_Api::getApi(array('mode' => $this->getEndpointMode()));
        $isCurrentCurrency  = $this->getIsUseCurrentCurrency();
        $price              = $isCurrentCurrency ? $payment->getAmountAuthorized() : $payment->getBaseAmountAuthorized();
        $priceCode          = $isCurrentCurrency ? Mage::app()->getStore()->getCurrentCurrencyCode() : Mage::app()->getStore()->getBaseCurrencyCode();

        // does not create charge on checkout.com if amount is 0
        if (empty($price)) {
            return $this;
        }

        $amount     = $Api->valueToDecimal($price, $priceCode);
        $config     = $this->_getCharge($amount, $isAutoCapture);

        $config['postedParam']['trackId'] = $payment->getOrder()->getIncrementId();

        $paymentTokenCharge = $Api->getPaymentToken($config);

        if ($Api->getExceptionState()->hasError()) {
            Mage::log($Api->getExceptionState()->getErrorMessage(), null, $this->_code.'.log');
            Mage::log($Api->getExceptionState(), null, $this->_code.'.log');

            $message = Mage::helper('payment')->__('An unrecoverable error occurred while processing your payment information.');
            Mage::throwException($message);
        }

        $paymentToken = $paymentTokenCharge->getId();

        $this->_buildRedirectData($config, $paymentToken);

        return $this;
    }

    /**
     * Set redirect URL to session
     *
     * @param $config
     * @param $paymentToken
     * @return $this
     */
    protected function _buildRedirectData($config, $paymentToken) { 

        $isUseCurrencyCode = $this->useCurrencyCode() ? 'true' : 'false';

        $params = array(
            'publicKey'         => $this->getPublicKey(),
            'paymentToken'      => $paymentToken,
            'customerEmail'     => $config['postedParam']['email'],
            'value'             => $config['postedParam']['value'],
            'currency'          => $config['postedParam']['currency'],
            'cardFormMode'      => self::CARD_FORM_MODE,
            'paymentMode'       => $this->getPaymentMode(),
            'environment'       => $this->getEndpointMode(),
            'redirectUrl'       => Mage::getModel('core/url')->getUrl('chargepayment/api/hosted/'),
            'cancelUrl'         => Mage::helper('checkout/url')->getCheckoutUrl(),
            'contextId'         => $config['postedParam']['trackId'],
            'billingDetails'    => $config['postedParam']['billingDetails'],
            'useCurrencyCode'   => $isUseCurrencyCode,
            'logoUrl'           => $this->getlogoUrl(),
            'themeColor'        => $this->getThemeColor(),
            'iconColor'         => $this->getIconColor(),
            'title'             => $this->getFormTitle(),
            'theme'             => 'standard'
        );

        $baseUrl = Mage::getModel('core/url')->getUrl('chargepayment/api/redirect/');
        $session = Mage::getSingleton('chargepayment/session_quote');

        $session
            ->setHostedPaymentRedirect($baseUrl)
            ->setHostedPaymentParams($params)
            ->setHostedPaymentConfig($config)
            ->setSecretKey($this->_getSecretKey());

        return $this;
    }

    /**
     * Return endpoint mode for payment
     *
     * @return mixed
     */
    public function getEndpointMode() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'mode');
    }

    /**
     * Return config value for using currency in payments
     *
     * @return mixed
     */
    public function getIsUseCurrentCurrency() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'current_currency');
    }

    /**
     * Return base data for charge
     *
     * @param null $amount
     * @param bool $autoCapture
     * @return array
     */
    private function _getCharge($amount = null, $autoCapture = false) {
        $secretKey          = $this->_getSecretKey();
        $isCurrentCurrency  = $this->getIsUseCurrentCurrency();

        $billingAddress     = $this->_getQuote()->getBillingAddress();
        $shippingAddress    = $this->_getQuote()->getShippingAddress();
        $orderedItems       = $this->_getQuote()->getAllItems();
        $currencyDesc       = $isCurrentCurrency ? Mage::app()->getStore()->getCurrentCurrencyCode() : Mage::app()->getStore()->getBaseCurrencyCode();
        $amountCents        = $amount;
        $chargeMode         = $this->getIs3D();
        $shippingCost       = $this->_getQuote()->getShippingAddress()->getShippingAmount();


        $street = Mage::helper('customer/address')
            ->convertStreetLines($billingAddress->getStreet(), 2);

        $billingAddressConfig = array (
            'addressLine1'  => $street[0],
            'addressLine2'  => $street[1],
            'postcode'      => $billingAddress->getPostcode(),
            'country'       => $billingAddress->getCountry(),
            'city'          => $billingAddress->getCity(),
            'state'         => $billingAddress->getRegion(),
            'phone'         => array('number' => $billingAddress->getTelephone())
        );

        $billingPhoneNumber = $billingAddress->getTelephone();

        if (!empty($billingPhoneNumber)) {
            $billingAddressConfig['phone'] = array('number' => $billingPhoneNumber);
        }

        $street = Mage::helper('customer/address')
            ->convertStreetLines($shippingAddress->getStreet(), 2);

        $shippingAddressConfig = array(
            'addressLine1'       => $street[0],
            'addressLine2'       => $street[1],
            'postcode'           => $shippingAddress->getPostcode(),
            'country'            => $shippingAddress->getCountry(),
            'city'               => $shippingAddress->getCity()
        );

        $phoneNumber = $shippingAddress->getTelephone();

        if (!empty($phoneNumber)) {
            $shippingAddressConfig['phone'] = array('number' => $phoneNumber);
        }

        $products = array();

        foreach ($orderedItems as $item) {
            $product        = Mage::getModel('catalog/product')->load($item->getProductId());
            $productPrice   = $item->getPrice();
            $productPrice   = is_null($productPrice) || empty($productPrice) ? 0 : $productPrice;
            $productImage   = $product->getImage();

            $products[] = array (
                'name'       => $item->getName(),
                'sku'        => $item->getSku(),
                'price'      => $productPrice,
                'quantity'   => $item->getQty(),
                'image'      => $productImage != 'no_selection' && !is_null($productImage) ? Mage::helper('catalog/image')->init($product , 'image')->__toString() : '',
                'shippingCost' => $shippingCost,
            );
        }

        $config                     = array();
        $config['authorization']    = $secretKey;

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $tmp = explode(',', $ip); 
        $ip = end($tmp);

        $config['postedParam'] = array (
            'trackId'           => NULL,
            'customerName'      => $billingAddress->getName(),
            'email'             => Mage::helper('chargepayment')->getCustomerEmail(),
            'value'             => $amountCents,
            'chargeMode'        => $chargeMode,
            'currency'          => $currencyDesc,
            'billingDetails'    => $billingAddressConfig,
            'shippingDetails'   => $shippingAddressConfig,
            'products'          => $products,
            'customerIp'        => $ip,
            'metadata'          => array(
                'server'            => Mage::helper('core/http')->getHttpUserAgent(),
                'quoteId'           => $this->_getQuote()->getId(),
                'magento_version'   => Mage::getVersion(),
                'plugin_version'    => Mage::helper('chargepayment')->getExtensionVersion(),
                'lib_version'       => CheckoutApi_Client_Constant::LIB_VERSION,
                'integration_type'  => 'HOSTED',
                'time'              => Mage::getModel('core/date')->date('Y-m-d H:i:s')
            )
        );

        $autoCapture = 'n';

        if ($this->getAutoCapture() ==1){
            $autoCapture = 'y';
        }

        $config['postedParam']['autoCapture']  = $autoCapture;
        $config['postedParam']['autoCapTime']  = $this->getAutoCapTime();

        return $config;
    }

    /**
     * Return true if action is Authorize and Capture
     *
     * @return bool
     */
    protected function _isAutoCapture() {
        $paymentAction  = Mage::helper('chargepayment')->getConfigData($this->_code, 'payment_action');

        return $paymentAction === CheckoutApi_ChargePayment_Helper_Data::PAYMENT_ACTION_AUTHORIZE_CAPTURE ? true : false;
    }

    /**
     * Return Order Status for New Order
     *
     * @return mixed
     *
     * @version 20160216
     */
    public function getNewOrderStatus() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'order_status');
    }

    /**
     * Return Quote from session
     *
     * @return mixed
     */
    private function _getQuote() {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * Return secret key from config
     *
     * @param null $storeId
     * @return bool
     */
    protected function _getSecretKey($storeId = NULL) {
        $secretKey = Mage::helper('chargepayment')->getConfigData($this->_code, 'secretkey', $storeId);

        return !empty($secretKey) ? $secretKey : false;
    }

    /**
     * Return true if is 3D
     *
     * @return bool
     */
    public function getIs3D() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'is_3d');
    }

    /**
     * Return Public key
     *
     * @return mixed
     */
    public function getPublicKey() { 
        return  Mage::helper('chargepayment')->getConfigData($this->_code, 'publickey');
    }

    /**
     * Return Payment Mode
     *
     * @return mixed
     */
    public function getPaymentMode() {
        $paymentMode = Mage::helper('chargepayment')->getConfigData($this->_code, 'payment_mode');

        return $paymentMode;
    }

    /**
     * Return Logo url
     *
     * @return mixed
     */
    public function getLogoUrl() { 
        return  Mage::helper('chargepayment')->getConfigData($this->_code, 'logo_url');
    }

    /**
     * Return Theme color
     *
     * @return mixed
     */
    public function getThemeColor() { 
        return  Mage::helper('chargepayment')->getConfigData($this->_code, 'theme_color');
    }

    /**
     * Return Use currency code
     *
     * @return mixed
     */
    public function useCurrencyCode() { 
        return  Mage::helper('chargepayment')->getConfigData($this->_code, 'use_currency_code');
    }

    /**
     * Return Form title
     *
     * @return mixed
     */
    public function getFormTitle() { 
        return  Mage::helper('chargepayment')->getConfigData($this->_code, 'form_title');
    }


    /**
     * Return Icon color
     *
     * @return mixed
     */
    public function getIconColor() { 
        return  Mage::helper('chargepayment')->getConfigData($this->_code, 'icon_color');
    }

    /**
     * Cancel payment
     *
     * @param Varien_Object $payment
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        $this->void($payment);

        return $this;
    }

    /**
     * For Refund
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     */
    public function refund(Varien_Object $payment, $amount) {
        $isDebug        = $this->isDebug();
        $refundData     = $this->_getVoidChargeData($payment, true);
        $order          = $payment->getOrder();

        $Api                    = CheckoutApi_Api::getApi(array('mode'=>$this->getEndpointMode()));
        $isCurrentCurrency      = $payment->getAdditionalInformation('use_current_currency');

        if ($isCurrentCurrency) {
            // Allowed currencies
            $amount = Mage::helper('directory')->currencyConvert($amount, $order->getBaseCurrencyCode(), $order->getOrderCurrencyCode());
            $amount = $Api->valueToDecimal($amount, $order->getOrderCurrencyCode());
        } else {
            $amount = $Api->valueToDecimal($amount, $order->getBaseCurrencyCode());
        }

        $refundData['postedParam']['value']     = $amount;
        $result                                 = $Api->refundCharge($refundData);

        if (is_object($result) && method_exists($result, 'toArray')) {
            Mage::log($result->toArray(), null, $this->_code.'.log');
        }

        if ($Api->getExceptionState()->hasError()) {
            Mage::log($Api->getExceptionState()->getErrorMessage(), null, $this->_code.'.log');
            Mage::log($Api->getExceptionState(), null, $this->_code.'.log');
            $errorMessage = Mage::helper('chargepayment')->__('Your payment was not completed.'. $Api->getExceptionState()->getErrorMessage().' and try again or contact customer support.');
            Mage::throwException($errorMessage);
        }

        if(!$result->isValid()) {
            if ($isDebug) {
                /* Capture processing error response. */
                $errors             = $result->toArray();

                if (!empty($errors['errorCode'])) {
                    $responseCode       = (int)$errors['errorCode'];
                    $responseMessage    = (string)$errors['message'];
                    $errorMessage       = "Error Code - {$responseCode}. Message - {$responseMessage};";
                } else {
                    $errorMessage = Mage::helper('chargepayment')->__('Refund action is not available.');
                }
            } else {
                $errorMessage = Mage::helper('chargepayment')->__('Refund action is not available.');
            }

            Mage::throwException($errorMessage);
            Mage::log($result->printError(), null, $this->_code.'.log');
        }

        $payment->setTransactionId($result->getId());
        $payment->setIsTransactionClosed(1);

        $order->setChargeIsRefunded(1);
        $order->save();

        return $this;
    }

    /**
     * Void payment
     *
     * @param Varien_Object $payment
     * @return Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment)
    {
        $isDebug        = $this->isDebug();
        $voidData       = $this->_getVoidChargeData($payment);

        $Api            = CheckoutApi_Api::getApi(array('mode'=>$this->getEndpointMode()));
        $result         = $Api->voidCharge($voidData);

        if (is_object($result) && method_exists($result, 'toArray')) {
            Mage::log($result->toArray(), null, $this->_code . '.log');
        }

        if ($Api->getExceptionState()->hasError()) {
            Mage::log($Api->getExceptionState()->getErrorMessage(), null, $this->_code.'.log');
            Mage::log($Api->getExceptionState(), null, $this->_code.'.log');
            $errorMessage = Mage::helper('chargepayment')->__('Your payment was not completed.'. $Api->getExceptionState()->getErrorMessage().' and try again or contact customer support.');
            Mage::throwException($errorMessage);
        }

        if($result->isValid()) {
            $payment->setTransactionId($result->getId());

            $order = $payment->getOrder();
            $order->setChargeIsVoided(1);
            $order->save();
        } else {
            if ($isDebug) {
                /* Capture processing error response. */
                $errors             = $result->toArray();

                if (!empty($errors['errorCode'])) {
                    $responseCode       = (int)$errors['errorCode'];
                    $responseMessage    = (string)$errors['message'];
                    $errorMessage       = "Error Code - {$responseCode}. Message - {$responseMessage};";
                } else {
                    $errorMessage = Mage::helper('chargepayment')->__('Void action is not available.');
                }
            } else {
                $errorMessage = Mage::helper('chargepayment')->__('Void action is not available.');
            }

            Mage::throwException($errorMessage);
            Mage::log($result->printError(), null, $this->_code.'.log');
        }

        return $this;
    }

    /**
     * Return array for Void Charge
     *
     * @param $payment
     * @param bool $isRefund
     * @return mixed
     * @throws Mage_Core_Exception
     */
    protected function _getVoidChargeData($payment, $isRefund = false) {
        $config         = array();
        $order          = $payment->getOrder();
        $orderId        = $order->getIncrementId();
        $secretKey      = $this->_getSecretKey($order->getStoreId());
        $items          = $order->getAllItems();
        $products       = array();

        if (!$secretKey) {
            Mage::throwException(Mage::helper('chargepayment')->__('Payment method is not available.'));
        }

        foreach ($items as $item) {
            $product        = Mage::getModel('catalog/product')->load($item->getProductId());
            $productPrice   = $item->getPrice();
            $productPrice   = is_null($productPrice) || empty($productPrice) ? 0 : $productPrice;
            $productImage   = $product->getImage();

            $products[] = array (
                'name'       => $item->getName(),
                'sku'        => $item->getSku(),
                'price'      => $productPrice,
                'quantity'   => $isRefund ? (int)$item->getQtyRefunded() : (int)$item->getQty(),
                'image'      => $productImage != 'no_selection' && !is_null($productImage) ? Mage::helper('catalog/image')->init($product , 'image')->__toString() : '',

            );
        }

        $config['trackId']      = $orderId;
        $config['description']  = 'Description';
        $config['products']     = $products;

        $result['authorization']    = $secretKey;
        $result['postedParam']      = $config;
        $result['chargeId']         = $payment->getParentTransactionId();

        return $result;
    }

    /**
     * Return debug value
     *
     * @return mixed
     */
    public function isDebug() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'debug');
    }

    /**
     * Format price with currency sign
     *
     * @param $order
     * @param $amount
     * @param null $currency
     * @return mixed
     */
    protected function _formatPrice($order, $amount, $currency = null)
    {
        return $order->getBaseCurrency()->formatTxt(
            $amount,
            $currency ? array('currency' => $currency) : array()
        );
    }

    /**
     * Authorize By Card Token
     *
     * @param Mage_Sales_Model_Order $order
     * @param $cardToken
     * @return array
     * @throws Exception
     */
    public function authorizeByCardToken(Mage_Sales_Model_Order $order, $cardToken) {
        $isCurrentCurrency  = $this->getIsUseCurrentCurrency();
        $autoCapture        = $this->getAutoCapture();
        $session            = Mage::getSingleton('chargepayment/session_quote');
        $result             = array('status' => 'error', 'redirect' => Mage::helper('checkout/url')->getCheckoutUrl());

        $price = $isCurrentCurrency ? $order->getGrandTotal() : $order->getBaseGrandTotal();
        $priceCode = $isCurrentCurrency ? $this->getCurrencyCode() : Mage::app()->getStore()->getBaseCurrencyCode();

        $Api    = CheckoutApi_Api::getApi(array('mode' => $this->getEndpointMode()));
        $amount = $Api->valueToDecimal($price, $priceCode);
        $config = $session->getHostedPaymentConfig();

        if(empty($config)){
            Mage::log('Empty config', null, $this->_code.'.log');
            return false;
        }

        if (preg_match('/card_tok/',$cardToken)){
             $config['postedParam']['cardToken'] = $cardToken;
        } else {
             $config['postedParam']['cardId'] = $cardToken;
        }

        $config['postedParam']['trackId']   = $order->getIncrementId();
       

        $response = $Api->createCharge($config);

        if ($Api->getExceptionState()->hasError()) {
            Mage::log($Api->getExceptionState()->getErrorMessage(), null, $this->_code.'.log');
            Mage::log($Api->getExceptionState(), null, $this->_code.'.log');
            return $result;
        }

        if(!$response->isValid() || !$this->_responseValidation($response)) {
            return $result;
        }

        if (is_object($response) && method_exists($response, 'toArray')) {
            Mage::log($response->toArray(), null, $this->_code.'.log');
        }

        $toValidate = array(
            'currency' => $priceCode,
            'value'    =>  $Api->valueToDecimal($isCurrentCurrency ? $order->getGrandTotal() : $order->getBaseGrandTotal(), $priceCode),
        );
        $validateRequest = $Api->validateRequest($toValidate,$response);

        $payment            = $order->getPayment();
        $redirectUrl        = $response->getRedirectUrl();
        $entityId           = $response->getId();

        if ($redirectUrl && $entityId) {
            $payment
                ->setAdditionalInformation('payment_token', $entityId)
                ->setAdditionalInformation('payment_token_url', $redirectUrl)
                ->setAdditionalInformation('use_current_currency', $isCurrentCurrency);
            $payment->save();
            $order->save();

            $session->addPaymentToken($entityId);
            $session
                ->setIs3d(true)
                ->setPaymentRedirectUrl($redirectUrl)
                ->setEndpointMode($this->getEndpointMode())
                ->setSecretKey($this->_getSecretKey())
                ->setNewOrderStatus($this->getNewOrderStatus());

            $result = array('status' => '3d', 'redirect' => $redirectUrl);
        } else {

        Mage::getModel('chargepayment/customerCard')->saveCard($payment, $response);

            $payment
                ->setTransactionId($entityId)
                ->setCurrencyCode($order->getBaseCurrencyCode())
                ->setPreparedMessage((string)$response->getDescription())
                ->setIsTransactionClosed(0)
                ->setShouldCloseParentTransaction(false)
                ->setBaseAmountAuthorized($amount)
                ->setAdditionalInformation('use_current_currency', $isCurrentCurrency);

            if ($autoCapture) {
                $message = Mage::helper('sales')->__('Capturing amount of %s is pending approval on gateway.', $this->_formatPrice($order, $price));

                $payment->setIsTransactionPending(true);
                $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, null, false , '');
            } else {
                $message = Mage::helper('sales')->__('Authorized amount of %s.',$this->_formatPrice($order, $price));
                $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, false , '');
            }

            $message .= ' ' . Mage::helper('sales')->__('Transaction ID: "%s".', $entityId);

            if($validateRequest['status'] == 1 && (int)$response->getResponseCode() === CheckoutApi_ChargePayment_Model_Checkout::CHECKOUT_API_RESPONSE_CODE_APPROVED ){
                $order->setStatus($this->getNewOrderStatus());
                $order->addStatusHistoryComment($message, false);
            } else {
                $fraudmessage = $message.' '. Mage::helper('sales')->__(' Suspected fraud - Please verify amount and quantity.');
                $order->setState('payment_review');
                $order->setStatus('fraud');
                $order->addStatusHistoryComment($fraudmessage, false);
            }

            $order->save();
            $order->sendNewOrderEmail();
            
            $cart = Mage::getSingleton('checkout/cart');
            $cart->truncate()->save();

            $session->setIs3d(false);

            $result = array('status' => 'success', 'redirect' => 'checkout/onepage/success');
        }

        return $result;
    }

    /**
     * For capture
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     */
    public function capture(Varien_Object $payment, $amount) {
        $isCapture  = $payment->getChargeIsCaptured();

        /* If Charge already Captured by Checkout Api is auto Invoice */
        if ($isCapture) {
            return $this;
        }

        /* does not create charge on checkout.com if amount is 0 */
        if ($amount <= 0) {
            return $this;
        }

        $transactionId  = $payment->getParentTransactionId();
        $isDebug        = $this->isDebug();

        if (empty($transactionId)) {
            Mage::throwException('Invalid transaction ID.');
        }

        $order              = $payment->getOrder();
        $Api                = CheckoutApi_Api::getApi(array('mode'=>$this->getEndpointMode()));
        $isCurrentCurrency  = $payment->getAdditionalInformation('use_current_currency');

        $amount         = $isCurrentCurrency ? $order->getGrandTotal() : $order->getBaseGrandTotal();
        $amount         = $Api->valueToDecimal($amount, $isCurrentCurrency ? $order->getOrderCurrencyCode(): $order->getBaseCurrencyCode());
        $captureData    = $this->_getCaptureChargeData($payment, $amount);
        $result         = $Api->captureCharge($captureData);

        if (is_object($result) && method_exists($result, 'toArray')) {
            Mage::log($result->toArray(), null, $this->_code.'.log');
        }

        if ($Api->getExceptionState()->hasError()) {
            Mage::log($Api->getExceptionState()->getErrorMessage(), null, $this->_code.'.log');
            Mage::log($Api->getExceptionState(), null, $this->_code.'.log');
            $errorMessage = Mage::helper('chargepayment')->__('Your payment was not completed.'. $Api->getExceptionState()->getErrorMessage().' and try again or contact customer support.');
            Mage::throwException($errorMessage);
        }

        if($result->isValid()) {
            $payment->setTransactionId($result->getId())
                ->setIsTransactionClosed(1)
                ->setCurrencyCode($order->getBaseCurrencyCode());

            $order->setChargeIsCaptured(1);
            $order->save();
        } else {
            if ($isDebug) {
                /* Capture processing error response. */
                $errors             = $result->toArray();

                if (!empty($errors['errorCode'])) {
                    $responseCode       = (int)$errors['errorCode'];
                    $responseMessage    = (string)$errors['message'];
                    $errorMessage       = "Error Code - {$responseCode}. Message - {$responseMessage}.";
                } else {
                    $errorMessage = Mage::helper('chargepayment')->__('Capture action is not available.');
                }
            } else {
                $errorMessage = Mage::helper('chargepayment')->__('Capture action is not available.');
            }

            Mage::throwException($errorMessage);
            Mage::log($result->printError(), null, $this->_code.'.log');
        }

        return $this;
    }

    /**
     * Return array for capture charge
     *
     * @param $payment
     * @param $amount
     * @return array
     * @throws Mage_Core_Exception
     */
    protected function _getCaptureChargeData($payment, $amount) {
        $config         = array();
        $order          = $payment->getOrder();
        $orderId        = $order->getIncrementId();

        if (!$order) {
            Mage::throwException('Order is empty.');
        }

        $secretKey      = $this->_getSecretKey($order->getStoreId());

        if (!$secretKey) {
            Mage::throwException(Mage::helper('chargepayment')->__('Payment method is not available.'));
        }

        $config['value']        = $amount;
        $config['trackId']      = $orderId;
        $config['description']  = 'capture description';

        $result['authorization']    = $secretKey;
        $result['postedParam']      = $config;
        $result['chargeId']         = $payment->getParentTransactionId();

        return $result;
    }

    /**
     * Validate Response Object by Response Code
     *
     * @param $response
     * @return bool
     */
    protected function _responseValidation($response) {
        $responseCode       = (int)$response->getResponseCode();
        $status             = (string)$response->getStatus();
        $responseMessage    = (string)$response->getResponseMessage();

        if ($responseCode !== CheckoutApi_ChargePayment_Model_Checkout::CHECKOUT_API_RESPONSE_CODE_APPROVED
            && $responseCode !== CheckoutApi_ChargePayment_Model_Checkout::CHECKOUT_API_RESPONSE_CODE_APPROVED_RISK) {
            Mage::log("Please check you card details and try again. Thank you", null, $this->_code.'.log');

            return false;
        }

        return true;
    }

    /**
     * Return Order Status For Void
     *
     * @return mixed
     */
    public function getVoidStatus() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'void_status');
    }

    /**
     * Return Public Key for WebHook
     *
     * @return mixed
     */
    public function getPublicKeyWebHook() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'publickey_web');
    }


    public function getAutoCapTime(){
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'autoCapTime');
    }

    public function getAutoCapture(){
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'autoCapture');
    }

    public function getSecretKey() {
        return  Mage::helper('chargepayment')->getConfigData($this->_code, 'secretkey');
    }

    public function getMode() {
        return  Mage::helper('chargepayment')->getConfigData($this->_code, 'mode');
    }

    public function getCustomerId() { 
        if (Mage::app()->getStore()->isAdmin()) {
            $customerId = Mage::getSingleton('adminhtml/session_quote')->getCustomerId();
        } else {
            $customerId = Mage::getModel('checkout/cart')->getQuote()->getCustomerId();
        }

        if(is_null($customerId)){
            $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
        }

        return $customerId ? $customerId : false;
    }

    public function getSaveCardSetting(){
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'saveCard');
    }
}