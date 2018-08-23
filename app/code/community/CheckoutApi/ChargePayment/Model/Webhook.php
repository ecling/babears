<?php

/**
 * Class for Checkout.com Webhooks
 *
 * Class CheckoutApi_ChargePayment_Model_Webhook
 *
 * @version 20151113
 */
class CheckoutApi_ChargePayment_Model_Webhook
{
    const LOG_FILE                      = 'checkout_api_webhook.log';
    const EVENT_TYPE_CHARGE_SUCCEEDED   = 'charge.succeeded';
    const EVENT_TYPE_CHARGE_CAPTURED    = 'charge.captured';
    const EVENT_TYPE_CHARGE_REFUNDED    = 'charge.refunded';
    const EVENT_TYPE_CHARGE_VOIDED      = 'charge.voided';
    const EVENT_TYPE_INVOICE_CANCELLED  = 'invoice.cancelled';

    /**
     * Check if Webhook data valid
     *
     * @param $response
     * @return bool
     *
     * @version 20151113
     */
    public function isValidResponse($response) {
        if (empty($response) || !property_exists($response->message, 'trackId')) {
            return false;
        }

        $responseCode       = (int)$response->message->responseCode;
        $status             = property_exists($response->message, 'status') ? (string)$response->message->status : '';
        $responseMessage    = property_exists($response->message, 'responseMessage') ? (string)$response->message->responseMessage : '';
        $trackId            = (string)$response->message->trackId;

        if ($responseCode !== CheckoutApi_ChargePayment_Model_CreditCard::CHECKOUT_API_RESPONSE_CODE_APPROVED &&
            $responseCode !== CheckoutApi_ChargePayment_Model_CreditCard::CHECKOUT_API_RESPONSE_CODE_APPROVED_RISK) {

            $message = "Error Code - {$responseCode}. Message - {$responseMessage}. Status - {$status}. Order - {$trackId}";
            Mage::log($message, null, self::LOG_FILE);

            return false;
        }

        return true;
    }

    /**
     * For authorise order
     *
     * @param $response
     * @return bool
     *
     * @version 20161212
    */
    public function authoriseOrder($response){
        return true;
    }


    /**
     * For capture order
     *
     * @param $response
     * @return bool
     *
     * @version 20151116
     */
    public function captureOrder($response) {
        $trackId    = (string)$response->message->trackId;
        $modelOrder = Mage::getModel('sales/order');
        $order      = $modelOrder->loadByIncrementId($trackId);
        $orderId    = $order->getId();
        $qty        = $order->getData('total_qty_ordered');
        $chargeMode = $response->message->chargeMode;

        if (!$orderId || empty($qty)) {
            Mage::log("Cannot create an invoice. Order - {$trackId}", null, self::LOG_FILE);
            return false;
        }

        /* if charge already captured by API */
        $chargeIsCaptured = $order->getChargeIsCaptured();

        if (!empty($chargeIsCaptured)) {
            return false;
        }

        if (Mage::getSingleton('core/session')->getData('checkout_api_capture_' . $trackId)) {
            return false;
        }

        Mage::getSingleton('core/session')->setData('checkout_api_capture_' . $trackId, true);

        $amount     = $order->getBaseGrandTotal();

        $payment = $order->getPayment();
        $parentTransactionId    = $payment->getLastTransId();
        $transactionCollection  = Mage::getModel('sales/order_payment_transaction')
            ->getCollection()
            ->addAttributeToFilter('order_id', array('eq' => $orderId))
            ->addPaymentIdFilter($payment->getId());

        $collectionCount = $transactionCollection->count();

        if (!$collectionCount && $chargeMode !== 3) {
            Mage::log("Cannot create an invoice. Order - {$trackId}. Empty transactions.", null, self::LOG_FILE);
            return false;
        }

        try {
            $payment
                ->setTransactionId((string)$response->message->id)
                ->setCurrencyCode($order->getBaseCurrencyCode())
                ->setPreparedMessage((string)$response->message->description)
                ->setParentTransactionId($parentTransactionId)
                ->setShouldCloseParentTransaction(true)
                ->setIsTransactionClosed(true)
                ->registerCaptureNotification($amount, true)
            ;

            $order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
            $order->save();

            //付款成功增加邮件发送
            $order->sendNewOrderEmail();

            $order = $modelOrder->loadByIncrementId($trackId);

            if ($order->hasInvoices()) {
                $invIncrementId = array();
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $invoiceIncId[] = $invoice->getIncrementId();
                }

                $incrementId = $invoiceIncId[0];

                $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($incrementId);
                $invoice->setTransactionId((string)$response->message->id);
                $invoice->save();
            }
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage(), null, self::LOG_FILE);
        }

        $order->setChargeIsCaptured(1);
        $order->save();
        Mage::getSingleton('core/session')->unsetData('checkout_api_capture_' . $trackId);

        return true;
    }

    /**
     * Compare Public Key from Headers with Key from store
     *
     * @param $key
     * @return bool
     *
     * @version 20151116
     */
    public function isValidPublicKey($key) {
        if (empty($key)) {
            Mage::log("Public shared keys is empty.", null, self::LOG_FILE);
            return false;
        }

        $publicKey      = Mage::getModel('chargepayment/creditCard')->getPublicKey();
        $publicJsKey    = Mage::getModel('chargepayment/creditCardJs')->getPublicKeyWebHook();
        $publicKitKey   = Mage::getModel('chargepayment/creditCardKit')->getPublicKeyWebHook();
        $publicHostedKey    = Mage::getModel('chargepayment/hosted')->getPublicKeyWebHook();

        $result         = $publicKey === $key || $publicJsKey === $key || $publicKitKey === $key || $publicHostedKey ? true : false;

        if (!$result) {
            Mage::log("Public shared keys {$key} (API) and {$publicKey} (Magento) do not match.", null, self::LOG_FILE);
        }

        return $result;
    }

    /**
     * For refund Order by Webhook
     *
     * @param $response
     * @return bool
     *
     * @version 20151130
     */
    public function refundOrder($response) { 
        $trackId        = (string)$response->message->trackId;
        $transactionId  = (string)$response->message->id;
        $modelOrder     = Mage::getModel('sales/order');
        $order          = $modelOrder->loadByIncrementId($trackId);
        $orderId        = $order->getId();

        if (!$orderId) {
            Mage::log("Cannot refund Order - {$trackId}", null, self::LOG_FILE);
            return false;
        }

        /* if charge already refunded by API */
        $chargeIsRefunded = $order->getChargeIsRefunded();

        if (!empty($chargeIsRefunded)) {
            return false;
        }

        try {
            $service        = Mage::getModel('sales/service_order', $order);
            $productModel   = Mage::getModel('catalog/product');

            $data           = array();
            $productList    = $response->message->products;

            if (count($productList)) {
                foreach($productList as $index => $product) {
                    $productId = $productModel->getIdBySku((string)$product->sku);

                    if (!$productId) {
                        continue;
                    }

                    $data['qty'][$index] = array($productId => (int)$product->quantity);
                }
            }

            $isCurrentCurrency = $order->getPayment()->getAdditionalInformation('use_current_currency');

            $amountDecimal  = $response->message->value;
            $liveMode       = $response->message->liveMode;
            $Api            = CheckoutApi_Api::getApi(array('mode' => $liveMode ? CheckoutApi_ChargePayment_Helper_Data::API_MODE_LIVE : CheckoutApi_ChargePayment_Helper_Data::API_MODE_SANDBOX));

            if ($isCurrentCurrency) {
                // Allowed currencies
                $amount             = $Api->decimalToValue($amountDecimal, $order->getOrderCurrencyCode());
                $amount             = $amount/$order->getBaseToOrderRate();
                $amount             =  Mage::app()->getStore()->roundPrice($amount);
                $amountOrder        = $Api->valueToDecimal($order->getGrandTotal(), $order->getOrderCurrencyCode());
            } else {
                $amount             = $Api->decimalToValue($amountDecimal, $order->getBaseCurrencyCode());
                $amountOrder        = $order->getBaseGrandTotal();
            }

            if ($amount < $amountOrder) {
                $data['adjustment_negative']    = $order->getBaseGrandTotal() - $amount;
                $formattedAmount                = $order->getBaseCurrency()->formatTxt($amount, array());
            } else {
                $formattedAmount    = $order->getBaseCurrency()->formatTxt($order->getBaseGrandTotal(), array());
            }

            $message = Mage::helper('sales')->__('Refunded amount of %s. Transaction ID: "%s"', $formattedAmount, $transactionId);

            $transactionModel = Mage::getModel('sales/order_payment_transaction');

            $transactionModel
                ->setOrderPaymentObject($order->getPayment())
                ->setTxnId($transactionId)
                ->setParentTxnId((string)$response->message->originalId)
                ->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND)
                ->save();

            $creditMemo = $service->prepareCreditmemo($data)
                ->setPaymentRefundDisallowed(true)
                ->setAutomaticallyCreated(true)
                ->register()
                ->setTransactionId((string)$response->message->id)
                ->addComment((string)$response->message->description)
                ->save();

            $order->setChargeIsRefunded(1);
            $order->addStatusHistoryComment($message);
            $order->setStatus(Mage_Sales_Model_Order::STATE_CLOSED);

            $order->save();
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage(), null, self::LOG_FILE);
        }

        return true;
    }

    /**
     * For void Order by Webhook
     *
     * @param $response
     * @return bool
     *
     * @version 20151130
     */
    public function voidOrder($response) { 
        $trackId    = (string)$response->message->trackId;
        $modelOrder = Mage::getModel('sales/order');
        $order      = $modelOrder->loadByIncrementId($trackId);

        if ($order->hasInvoices()) {
            $this->cancelInvoicedOrder($response, $order);
            return;
        }

        $orderId    = $order->getId();

        $transactionId          = (string)$response->message->id;
        $parentTransactionId    = (string)$response->message->originalId;
        $chargeMode             = (int)$response->message->chargeMode;

        if (!$orderId) {
            Mage::log("Cannot void Order - {$trackId}", null, self::LOG_FILE);
            return false;
        }

        /* if charge already refunded by API */
        $chargeIsVoided = $order->getChargeIsVoided();

        if (!empty($chargeIsVoided)) {
            return false;
        }

        $transactionCollection  = Mage::getModel('sales/order_payment_transaction')
            ->getCollection()
            ->addAttributeToFilter('order_id', array('eq' => $orderId))
            ->addPaymentIdFilter($order->getPayment()->getId());

        $collectionCount = $transactionCollection->count();

        if (!$collectionCount) {
            Mage::log("Cannot void Order - {$trackId}. Empty transactions.", null, self::LOG_FILE);
            return false;
        }

        $isVoid     = false;
        $payment    = $order->getPayment();

        foreach ($transactionCollection as $transaction) {
            $transactionTxnId   = $transaction->getTxnId();
            $transactionStatus  = $transaction->getTxnType();
            $isClosed           = (int)$transaction->getIsClosed();

            if($chargeMode === 3){
                if ( !$isClosed) {
                    $isVoid = true;
                    break;
                }
            } else {
                if ($parentTransactionId === $transactionTxnId && !$isClosed) {

                    $isVoid = true;
                    break;
                }
            }
        }

        if (!$isVoid) {
            Mage::log("Cannot void Order - {$trackId}. Empty transactions.", null, self::LOG_FILE);
            return false;
        }

        try {

            $payment
                ->setTransactionId($transactionId)
                ->setCurrencyCode($order->getBaseCurrencyCode())
                ->setPreparedMessage((string)$response->message->description)
                ->setParentTransactionId($parentTransactionId)
                ->setShouldCloseParentTransaction(true)
                ->setIsTransactionClosed(true)
                ;
            
            $isCancelledOrderCard   = Mage::getModel('chargepayment/creditCard')->getVoidStatus();
            $isCancelledOrderJs     = Mage::getModel('chargepayment/creditCardJs')->getVoidStatus();
            $isCancelledOrderKit    = Mage::getModel('chargepayment/creditCardKit')->getVoidStatus();
            $isCancelledOrderHosted = Mage::getModel('chargepayment/hosted')->getVoidStatus();

            $isCancelledOrder       = $isCancelledOrderCard || $isCancelledOrderJs || $isCancelledOrderKit || $isCancelledOrderHosted ? true : false;

            if ($isCancelledOrder) {
                $order->setStatus('canceled');
                $order->setState('canceled');
                $order->addStatusHistoryComment('Transaction has been voided', true);
            }

            $order->setChargeIsVoided(1);
            $order->save();;

        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage(), null, self::LOG_FILE);
        }

        return true;
    }

    /**
     * Void order for flagged transaction
     *
     * @param $response
     * @param $order
     */
    public function cancelInvoicedOrder($response, $order) {
        $transactionId  = (string)$response->message->id;
        $message        = Mage::helper('sales')->__('Transaction has been void.');
        $voidMessage    = (string)$response->message->description;

        $voidMessage .= ' ' . Mage::helper('sales')->__('Registered a Void notification.');

        $amount         = (float)$order->getBaseGrandTotal();

        if ($amount) {
            $amount = Mage::app()->getStore()->roundPrice($amount);
            $voidMessage .= ' ' . Mage::helper('sales')->__('Amount: %s.', $order->getBaseCurrency()->formatTxt($amount, array()));
        }

        $voidMessage .= ' ' . Mage::helper('sales')->__('Transaction ID: "%s".', $transactionId);

        try {
            foreach ($order->getInvoiceCollection() as $invoice) {
                $invoiceObj = Mage::getModel('sales/order_invoice')->loadByIncrementId($invoice->getIncrementId());
                $invoiceObj->setState(Mage_Sales_Model_Order::STATE_CANCELED);
                $invoiceObj->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
                $invoiceObj->save();
            }

            $transactionModel = Mage::getModel('sales/order_payment_transaction');
            $transactionModel
                ->setOrderPaymentObject($order->getPayment())
                ->setTxnId($transactionId)
                ->setParentTxnId((string)$response->message->originalId)
                ->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID)
                ->save();

            $order->registerCancellation($message);
            $order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
            $order->addStatusHistoryComment($voidMessage);
            $order->save();;
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage(), null, self::LOG_FILE);
        }
    }

    /**
     * Convert Quote to Order if tokens match
     *
     * @param $responseToken
     * @return bool
     *
     * @version 20160216
     */
    public function authorizeByPaymentToken($responseToken) {
        $result = array('error' => true, 'order_increment_id' => null, 'is_admin' => false);

        if (empty($responseToken)) {
            return $result;
        }

        $session        = Mage::getSingleton('chargepayment/session_quote');
        $Api            = CheckoutApi_Api::getApi(array('mode' => $session->getEndpointMode()));
        $verifyParams   = array('paymentToken' => $responseToken, 'authorization' => $session->getSecretKey());
        $response       = $Api->verifyChargePaymentToken($verifyParams);

        if (is_object($response) && method_exists($response, 'toArray')) {
            Mage::log($response->toArray(), null, self::LOG_FILE);
        }

        if ($Api->getExceptionState()->hasError()) {
            Mage::log($Api->getExceptionState()->getErrorMessage(), null, $this->_code.'.log');
            Mage::log($Api->getExceptionState(), null, $this->_code.'.log');
            $errorMessage = Mage::helper('chargepayment')->__('Your payment was not completed.'. $Api->getExceptionState()->getErrorMessage().' and try again or contact customer support.');
            Mage::throwException($errorMessage);
        }

        if(!$response->isValid() || !$this->_responseValidation($response)) {
            Mage::register('isSecureArea', true);
            $badOrderIncrementId = $response->getTrackId();
            Mage::log('Bad order increment id - ' . $badOrderIncrementId, null, $this->_code.'.log');
            if ($badOrderIncrementId) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($badOrderIncrementId);
                $invoices = $order->getInvoiceCollection();
                foreach ($invoices as $invoice){
                    //delete all invoice items
                    $items = $invoice->getAllItems();
                    foreach ($items as $item) {
                        $item->delete();
                    }
                    //delete invoice
                    $invoice->delete();
                }
            }

            $cancelOrderID = array('order_increment_id' =>$badOrderIncrementId );
            $result = array_merge($result,$cancelOrderID);

            return $result;
        }

        $chargeMode         = (int)$response->getChargeMode();

        if ($chargeMode !== CheckoutApi_ChargePayment_Helper_Data::CREDIT_CARD_CHARGE_MODE_3D
            && $chargeMode !== CheckoutApi_ChargePayment_Helper_Data::CREDIT_CARD_CHARGE_MODE_LP ) {
            return $result;
        }

        $transactionId      = (string)$response->getId();
        $orderIncrementId   = $response->getTrackId();
        $isAuto             = strtolower($response->getAutoCapture());
        $isAuto             = $isAuto == CheckoutApi_Client_Constant::AUTOCAPUTURE_AUTH ? false : true;

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);

        $isCurrentCurrency  = $session->getIsUseCurrentCurrency();
        $price              = $isCurrentCurrency ? $order->getGrandTotal() : $order->getBaseGrandTotal();
        $priceCode          = $isCurrentCurrency ? $this->getCurrencyCode() : Mage::app()->getStore()->getBaseCurrencyCode();

        $toValidate = array(
            'currency' => $priceCode,
            'value'    =>  $Api->valueToDecimal($price, $priceCode),
        );

        $validateRequest = $Api->validateRequest($toValidate,$response);

        if (!$order->getId()) {
            return $result;
        }

        $remoteIp = $order->getRemoteIp();

        if (empty($remoteIp)) {
            $result['is_admin'] = true;
        }

        if($chargeMode === CheckoutApi_ChargePayment_Helper_Data::CREDIT_CARD_CHARGE_MODE_3D){
            $storedToken = $order->getPayment()->getAdditionalInformation('payment_token');

            if ($storedToken !== $responseToken) {
                return $result;
            }
        }

        $payment    = $order->getPayment();
        $amount     = $order->getBaseGrandTotal();

        if($order->getStatus() == 'pending_payment'){
            try {
                Mage::getModel('chargepayment/customerCard')->saveCard($payment, $response);

                $payment
                    ->setTransactionId($transactionId)
                    ->setCurrencyCode($order->getBaseCurrencyCode())
                    ->setPreparedMessage((string)$response->getDescription())
                    ->setIsTransactionClosed(0)
                    ->setShouldCloseParentTransaction(false)
                    ->setBaseAmountAuthorized($amount)
                ;

                if ($isAuto) {
                    $message = Mage::helper('sales')->__('Capturing amount of %s is pending approval on gateway.', $this->_formatPrice($order, $amount));

                    $payment->setIsTransactionPending(true);
                    $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, null, false , '');
                } else {
                    $message = Mage::helper('sales')->__('Authorized amount of %s.', $this->_formatPrice($order, $amount));
                    $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, false , '');
                }

                $message .= ' ' . Mage::helper('sales')->__('Transaction ID: "%s".', $transactionId);

                $history = $order->addStatusHistoryComment($message, false);

                if($chargeMode === CheckoutApi_ChargePayment_Helper_Data::CREDIT_CARD_CHARGE_MODE_LP){
                        $order->setStatus('pending');
                } else { 

                     if($validateRequest['status'] !== 1 && (int)$response->getResponseCode() !== CheckoutApi_ChargePayment_Model_Checkout::CHECKOUT_API_RESPONSE_CODE_APPROVED){
                        $order->setState('payment_review');
                        $order->setStatus('fraud');
                        $order->addStatusHistoryComment('Suspected fraud - Please verify amount and quantity.', false);
                    } else { 
                        $order->setStatus('pending');
                    }
                }

                $order->save();

                if($chargeMode === CheckoutApi_ChargePayment_Helper_Data::CREDIT_CARD_CHARGE_MODE_3D){
                    Mage::getModel('chargepayment/customerCard')->saveCard($payment, $response);
                }

                $cart = Mage::getModel('checkout/cart');
                $cart->truncate()->save();

            } catch (Mage_Core_Exception $e) {
                Mage::log($e->getMessage(), null, self::LOG_FILE);
                return $result;
            }
        }

        $session->removeCheckoutOrderIncrementId($orderIncrementId);

        $result['error']                = false;
        $result['order_increment_id']   = $orderIncrementId;

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

        if ($responseCode !== CheckoutApi_ChargePayment_Model_Checkout::CHECKOUT_API_RESPONSE_CODE_APPROVED
            && $responseCode !== CheckoutApi_ChargePayment_Model_Checkout::CHECKOUT_API_RESPONSE_CODE_APPROVED_RISK) {
            return false;
        }

        return true;
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
}
