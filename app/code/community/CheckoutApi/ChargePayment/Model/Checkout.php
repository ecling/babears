<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Checkout
 *
 * @version 20160205
 */
abstract class CheckoutApi_ChargePayment_Model_Checkout extends Mage_Payment_Model_Method_Cc
{
    const AUTO_CAPTURE_TIME                         = 0;
    const CHECKOUT_API_RESPONSE_CODE_APPROVED       = 10000;
    const CHECKOUT_API_RESPONSE_CODE_APPROVED_RISK  = 10100;

    protected $_isGateway       = true;
    protected $_canUseInternal  = true;
    protected $_canUseCheckout  = true;
    protected $_canAuthorize    = true;
    protected $_canCapture      = true;
    protected $_canRefund       = true;

    protected $_canRefundInvoicePartial = true;
    protected $_canVoid         = true;
    protected $_canOrder        = true;
    protected $_canSaveCc       = false;

    public function getConfigPaymentAction() {
        return 'authorize';
    }

    /**
     * Redirect URL
     *
     * @return mixed
     *
     * @version 20160516
     */
    public abstract function getCheckoutRedirectUrl();

    /**
     * Redirect URL after order place
     *
     * @return mixed
     */
    public abstract function getOrderPlaceRedirectUrl();

    /**
     * Return debug value
     *
     * @return mixed
     *
     * @version 20151019
     */
    public function isDebug() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'debug');
    }

    /**
     * Return true if action is Authorize and Capture
     *
     * @return bool
     *
     * #version 20151020
     */
    protected function _isAutoCapture() {
        $paymentAction  = Mage::helper('chargepayment')->getConfigData($this->_code, 'payment_action');

        return $paymentAction === CheckoutApi_ChargePayment_Helper_Data::PAYMENT_ACTION_AUTHORIZE_CAPTURE ? true : false;
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
     * Cancel payment
     *
     * @param Varien_Object $payment
     * @return Mage_Payment_Model_Abstract
     *
     * @version 20151021
     */
    public function cancel(Varien_Object $payment)
    {
        $this->void($payment);

        return $this;
    }

    /**
     * Return endpoint mode for payment
     *
     * @return mixed
     *
     * @version 20151007
     */
    public function getEndpointMode() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'mode');
    }

    /**
     * Return Public Key for Webhook
     *
     * @return mixed
     *
     * @version 20151116
     */
    public function getPublicKey() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'publickey');
    }

    /**
     * Return Order Status For Void
     *
     * @return mixed
     *
     * @version 20151203
     */
    public function getVoidStatus() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'void_status');
    }

    /**
     * For capture
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     *
     * @version 20160204
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

        /* if parent transaction id is empty its authorize and capture */
        if (empty($transactionId)) {
            $this->authorize($payment, $amount);

            return $this;
        }

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
            if ($this->_responseValidation($result)) {
                $payment->setTransactionId($result->getId())
                    ->setIsTransactionClosed(1)
                    ->setCurrencyCode($order->getBaseCurrencyCode());

                $order->setChargeIsCaptured(1);
                $order->save();
            }
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
     *
     * @version 20160203
     */
    protected function _getCaptureChargeData($payment, $amount) {
        $config         = array();
        $order          = $payment->getOrder();
        $orderId        = $order->getIncrementId();

        if (!$order) {
            Mage::throwException($this->__('Order is empty.'));
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
     * @throws Mage_Core_Exception
     *
     * @version 20151028
     */
    protected function _responseValidation($response) {
        $responseCode       = (int)$response->getResponseCode();
        $status             = (string)$response->getStatus();
        $responseMessage    = (string)$response->getResponseMessage();

        if ($responseCode !== self::CHECKOUT_API_RESPONSE_CODE_APPROVED && $responseCode !== self::CHECKOUT_API_RESPONSE_CODE_APPROVED_RISK) {
            if ($this->isDebug()) {
                $message = "Error Code - {$responseCode}. Message - {$responseMessage}. Status - {$status}";;
            } else {
                $message = "Please check you card details and try again. Thank you";
            }

            Mage::throwException(Mage::helper('chargepayment')->__($message));
        }

        return true;
    }

    /**
     * For Refund
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     *
     * @version 20151020
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

        $refundData['postedParam']['value'] = $amount;
        $result                             = $Api->refundCharge($refundData);

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

        $this->_responseValidation($result);

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
     *
     * @version 20151021
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

        $this->_responseValidation($result);

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
     * Return current currency code
     *
     * @return string
     *
     * @version 20160204
     */
    public function getCurrencyCode() {
        return Mage::app()->getStore()->getCurrentCurrencyCode();
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
     * Return config value for using currency in payments
     *
     * @return mixed
     *
     * @version 20160301
     */
    public function getIsUseCurrentCurrency() {
        return Mage::helper('chargepayment')->getConfigData($this->_code, 'current_currency');
    }
}