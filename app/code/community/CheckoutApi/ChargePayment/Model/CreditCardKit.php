<?php

/**
 * Class for Checkout Kit payment method
 *
 * Class CheckoutApi_ChargePayment_Model_CreditCardKit
 *
 * @version 20160502
 */
class CheckoutApi_ChargePayment_Model_CreditCardKit extends CheckoutApi_ChargePayment_Model_Checkout
{
    protected $_code            = CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_KIT;
    protected $_canUseInternal  = false;

    protected $_formBlockType = 'chargepayment/form_checkoutApiKit';
    protected $_infoBlockType = 'chargepayment/info_checkoutApiKit';

    const RENDER_MODE           = 2;

    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info   = $this->getInfoInstance()
            ->setCheckoutApiCardId('')
            ->setPoNumber($data->getSaveCardCheck());

        $result = $this->_getSavedCartDataFromPost($data);

        if (!empty($result)) {
            $info->setCcType($result['cc_type']);
            $info->setCheckoutApiCardId($result['checkout_api_card_id']);
        }

        return $this;
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
     * Redirect URL
     *
     * @return mixed
     *
     * @version 20160516
     */
    public function getCheckoutRedirectUrl() {
        $controllerName     = (string)Mage::app()->getFrontController()->getRequest()->getControllerName();

        if ($controllerName === 'onepage') {
            return false;
        }

        $requestData        = Mage::app()->getRequest()->getParam('payment');
        $cardToken          = !empty($requestData['checkout_kit_card_token']) ? $requestData['checkout_kit_card_token'] : null;
        $session            = Mage::getSingleton('chargepayment/session_quote');

        if (!is_null($cardToken)) {
            return false;
        }

        $params['method']           = $this->_code;
        $params['kit_number']       = $requestData['checkout_kit_number'];
        $params['kit_name']         = $requestData['checkout_kit_name'];
        $params['kit_month']        = $requestData['checkout_kit_month'];
        $params['kit_year']         = $requestData['checkout_kit_year'];
        $params['kit_cvv']          = $requestData['checkout_kit_cvv'];
        $params['kit_public_key']   = Mage::helper('chargepayment')->getConfigData($this->_code, 'publickey');

        $session->setJsCheckoutApiParams($params);

        return Mage::helper('checkout/url')->getCheckoutUrl();
    }

    /**
     * Redirect URL after order place
     *
     * @return bool
     *
     * @version 20160516
     */
    public function getOrderPlaceRedirectUrl() {
        $session    = Mage::getSingleton('chargepayment/session_quote');
        $is3d       = $session->getIs3d();
        $is3dUrl    = $session->getPaymentRedirectUrl();

        $session
            ->setIs3d(false)
            ->setPaymentRedirectUrl(false);

        if ($is3d && $is3dUrl) {
            Mage::helper('chargepayment')->setOrderPendingPayment();

            return $is3dUrl;
        }

        return false;
    }

    /**
     * Return Quote from session
     *
     * @return mixed
     *
     * @version 20160505
     */
    protected function _getQuote() {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * Get Public Shared Key
     *
     * @return mixed
     *
     * @version 20160505
     */
    public function getPublicKeyWebHook() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'publickey_web');
    }

    /**
     * Return true if is 3D
     *
     * @return bool
     *
     * @version 20160505
     */
    public function getIs3D() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'is_3d');
    }

    /**
     * Return the timeout value for a request to the gateway.
     *
     * @return mixed
     *
     * @version 20160505
     */
    public function getTimeout() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'timeout');
    }

    /**
     * Validate payment method information object
     *
     * @return Mage_Payment_Model_Abstract
     *
     * @version 20160505
     */
    public function validate() {
        return $this;
    }

    /**
     * For authorize
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     * @throws Mage_Core_Exception
     *
     * @version 20160505
     */
    public function authorize(Varien_Object $payment, $amount) {
		// does not create charge on checkout.com if amount is 0
        if (empty($amount)) {
            return $this;
        }
		
        $requestData        = Mage::app()->getRequest()->getParam('payment');
        $session            = Mage::getSingleton('chargepayment/session_quote');
        $isCurrentCurrency  = $this->getIsUseCurrentCurrency();
        $order              = $payment->getOrder();

        /* Normal Payment */
        $cardToken      = !empty($requestData['checkout_kit_card_token']) ? $requestData['checkout_kit_card_token'] : NULL;
        $isDebug        = $this->isDebug();

        if (is_null($cardToken)) {
            $checkoutApiCardId = $payment->getCheckoutApiCardId();

            if(is_null($checkoutApiCardId)){
                Mage::throwException(Mage::helper('chargepayment')->__('Authorize action is not available.'));
                Mage::log('Empty Card Token or cardId', null, $this->_code.'.log');
            }
        }

        $price              = $isCurrentCurrency ? $order->getGrandTotal() : $order->getBaseGrandTotal();
        $priceCode          = $isCurrentCurrency ? $this->getCurrencyCode() : Mage::app()->getStore()->getBaseCurrencyCode();

        $Api    = CheckoutApi_Api::getApi(array('mode' => $this->getEndpointMode()));
        $amount = $Api->valueToDecimal($price, $priceCode);
        $config = $this->_getCharge($amount);

        $config['postedParam']['trackId']   = $payment->getOrder()->getIncrementId();

        if(isset($cardToken)){
            $config['postedParam']['cardToken'] = $cardToken;
        }

        if(isset($checkoutApiCardId)){
            $config['postedParam']['cardId'] = $checkoutApiCardId;
        }

        $result         = $Api->createCharge($config);

        if (is_object($result) && method_exists($result, 'toArray')) {
            Mage::log($result->toArray(), null, $this->_code.'.log');
        }

        if ($Api->getExceptionState()->hasError()) {
            Mage::log($Api->getExceptionState()->getErrorMessage(), null, $this->_code.'.log');
            Mage::log($Api->getExceptionState(), null, $this->_code.'.log');
            $errorMessage = Mage::helper('chargepayment')->__('Your payment was not completed.'. $Api->getExceptionState()->getErrorMessage().' and try again or contact customer support.');
            Mage::throwException($errorMessage);
        }

        $toValidate = array(
            'currency' => $priceCode,
            'value'    =>  $Api->valueToDecimal($price, $priceCode),
        );

        $validateRequest = $Api->validateRequest($toValidate,$result);

        if($result->isValid()) {
            if ($this->_responseValidation($result)) {
                /* Save Customer Credit Cart */
                $redirectUrl    = $result->getRedirectUrl();
                $entityId       = $result->getId();

                /* is 3D payment */
                if ($redirectUrl && $entityId) {
                    $payment
                        ->setAdditionalInformation('payment_token', $entityId)
                        ->setAdditionalInformation('payment_token_url', $redirectUrl)
                        ->setAdditionalInformation('use_current_currency', $isCurrentCurrency);

                    $session->addPaymentToken($entityId);
                    $session
                        ->setIs3d(true)
                        ->setPaymentRedirectUrl($redirectUrl)
                        ->setEndpointMode($this->getEndpointMode())
                        ->setSecretKey($this->_getSecretKey())
                        ->setNewOrderStatus($this->getNewOrderStatus())
                    ;
                } else {

                    Mage::getModel('chargepayment/customerCard')->saveCard($payment, $result);

                    $payment->setTransactionId($entityId);
                    $payment->setIsTransactionClosed(0);
                    $payment->setAdditionalInformation('use_current_currency', $isCurrentCurrency);


                    if($validateRequest['status']!== 1 && (int)$result->getResponseCode() !== CheckoutApi_ChargePayment_Model_Checkout::CHECKOUT_API_RESPONSE_CODE_APPROVED ){
                        $order->addStatusHistoryComment('Suspected fraud - Please verify amount and quantity.', false);
                        $payment->setIsFraudDetected(true);
                    } else {
                        $payment->setState('pending');
                    }

                    $session->setIs3d(false);
                }
            }
        } else {
            if ($isDebug) {
                /* Authorize processing error response. */
                $errors             = $result->toArray();

                if (!empty($errors['errorCode'])) {
                    $responseCode       = (int)$errors['errorCode'];
                    $responseMessage    = (string)$errors['message'];
                    $errorMessage       = "Error Code - {$responseCode}. Message - {$responseMessage}.";
                } else {
                    $errorMessage = Mage::helper('chargepayment')->__('Authorize action is not available.');
                }
            } else {
                $errorMessage = Mage::helper('chargepayment')->__('Authorize action is not available.');
            }

            Mage::throwException($errorMessage);
            Mage::log($result->printError(), null, $this->_code.'.log');
        }

        return $this;
    }

    /**
     * Return base data for charge
     *
     * @param null $amount
     * @return array
     *
     * @version 20160505
     */
    private function _getCharge($amount = null) {
        $secretKey          = $this->_getSecretKey();
        $isCurrentCurrency  = $this->getIsUseCurrentCurrency();

        $billingAddress     = $this->_getQuote()->getBillingAddress();
        $shippingAddress    = $this->_getQuote()->getBillingAddress();
        $orderedItems       = $this->_getQuote()->getAllItems();
        $currencyDesc       = $isCurrentCurrency ? $this->getCurrencyCode() : Mage::app()->getStore()->getBaseCurrencyCode();
        $amountCents        = $amount;
        $chargeMode         = $this->getIs3D();
        $shippingCost       = $this->_getQuote()->getShippingAddress()->getShippingAmount();

        $street = Mage::helper('customer/address')
            ->convertStreetLines($shippingAddress->getStreet(), 2);

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

        $shippingAddressConfig = array(
            'addressLine1'       => $street[0],
            'addressLine2'       => $street[1],
            'postcode'           => $shippingAddress->getPostcode(),
            'country'            => $shippingAddress->getCountry(),
            'city'               => $shippingAddress->getCity(),
            'phone'              => array('number' => $shippingAddress->getTelephone())
        );

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
                'shippingCost' => $shippingCost
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
                'server'  => Mage::helper('core/http')->getHttpUserAgent(),
                'quoteId' => $this->_getQuote()->getId(),
                'magento_version'   => Mage::getVersion(),
                'plugin_version'    => Mage::helper('chargepayment')->getExtensionVersion(),
                'lib_version'       => CheckoutApi_Client_Constant::LIB_VERSION,
                'integration_type'  => 'KIT',
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

    public function getAutoCapTime(){
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'autoCapTime');
    }

    public function getAutoCapture(){
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'autoCapture');
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