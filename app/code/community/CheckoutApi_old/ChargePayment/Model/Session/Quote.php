<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Session_Quote
 */
class CheckoutApi_ChargePayment_Model_Session_Quote extends Mage_Core_Model_Session_Abstract
{
    /**
     * Class constructor. Initialize session namespace
     */
    public function __construct()
    {
        $this->init('chargepayment_session');
    }

    /**
     * Add order IncrementId to session
     *
     * @param string $orderIncrementId
     *
     * @version 20160216
     */
    public function addCheckoutOrderIncrementId($orderIncrementId) {
        $orderIncIds = $this->getDirectPostOrderIncrementIds();
        if (!$orderIncIds) {
            $orderIncIds = array();
        }
        $orderIncIds[$orderIncrementId] = 1;
        $this->setDirectPostOrderIncrementIds($orderIncIds);
    }

    /**
     * Remove order IncrementId from session
     *
     * @param string $orderIncrementId
     *
     * @version 20160216
     */
    public function removeCheckoutOrderIncrementId($orderIncrementId) {
        $orderIncIds = $this->getDirectPostOrderIncrementIds();

        if (!is_array($orderIncIds)) {
            return;
        }

        if (isset($orderIncIds[$orderIncrementId])) {
            unset($orderIncIds[$orderIncrementId]);
        }
        $this->setDirectPostOrderIncrementIds($orderIncIds);
    }

    /**
     * Return if order incrementId is in session.
     *
     * @param string $orderIncrementId
     * @return bool
     *
     * @version 20160216
     */
    public function isCheckoutOrderIncrementIdExist($orderIncrementId) {
        $orderIncIds = $this->getDirectPostOrderIncrementIds();
        if (is_array($orderIncIds) && isset($orderIncIds[$orderIncrementId])) {
            return true;
        }
        return false;
    }

    /**
     * Add Payment Token for Local Payment to session
     *
     * @param $paymentToken
     *
     * @version 20160426
     */
    public function addCheckoutLocalPaymentToken($paymentToken) {
        $paymentTokens = $this->getCheckoutLocalPaymentTokens();
        $paymentTokens = !$paymentTokens ? array() : $paymentTokens;

        $paymentTokens[] = strtolower($paymentToken);
        $this->setCheckoutLocalPaymentTokens($paymentTokens);
    }

    /**
     * Remove Payment Token for Local Payment from session
     *
     * @param $paymentToken
     *
     * @version 20160426
     */
    public function removeCheckoutLocalPaymentToken($paymentToken) {
        $paymentTokens = $this->getCheckoutLocalPaymentTokens();

        if (!is_array($paymentTokens)) {
            return;
        }

        $result = array();

        foreach($paymentTokens as $index => $row) {
            if ($row !== $paymentToken) {
                $result[] = $row;
            }
        }

        $this->setCheckoutLocalPaymentTokens($result);
    }

    /**
     * Return if Payment Token is in session.
     *
     * @param $paymentToken
     * @return bool
     *
     * @version 20160426
     */
    public function isCheckoutLocalPaymentTokenExist($paymentToken) {
        $result = false;

        $mode =  Mage::getModel('chargepayment/creditCardJs')->getMode();
        $secretKey = Mage::getModel('chargepayment/creditCardJs')->getSecretKey();

        if(empty($secretKey) && empty($mode)){
            $secretKey = Mage::getModel('chargepayment/hosted')->getSecretKey();
            $mode = Mage::getModel('chargepayment/hosted')->getMode();
        }

        if(!is_null($paymentToken)){
            $Api = CheckoutApi_Api::getApi(array('mode' =>$mode ));

            $verifyParams   = array('paymentToken' => $paymentToken,
                'authorization' => $secretKey
            );

            $data = $Api->verifyChargePaymentToken($verifyParams);


            if($data->getResponseCode() == 10000 && $data->getChargeMode() === 3){
                $session        = Mage::getSingleton('chargepayment/session_quote');
                $order = Mage::getModel('sales/order')->loadByIncrementId($session->last_order_increment_id);
                $amount = $data->getValue();
                $currencyCode = $data->getCurrency();

                $amountCent = $Api->decimalToValue($amount, $currencyCode);

                $message = Mage::helper('sales')->__('Capturing amount of %s is pending approval on gateway.', $currencyCode.''.$amountCent);

                $order->addStatusHistoryComment($message);
                $order->setStatus('pending');
                $order->setState('new');
                $order->save();

                $result = true;
            }
        }

        return $result;
    }

    /**
     * Add Payment Token to session
     *
     * @param $paymentToken
     */
    public function addPaymentToken($paymentToken) {
        $paymentTokens = $this->getPaymentTokens();
        $paymentTokens = !$paymentTokens ? array() : $paymentTokens;

        $paymentTokens[] = strtolower($paymentToken);
        $this->setPaymentTokens($paymentTokens);
    }

    /**
     * Return if Payment Token is in session.
     *
     * @param $paymentToken
     * @return bool
     */
    public function isPaymentTokenExist($paymentToken) {
        $paymentTokens  = $this->getPaymentTokens();
        $result         = false;

        if (!is_array($paymentTokens)) {
            return $result;
        }

        foreach($paymentTokens as $row) {
            if ($row === $paymentToken) {
                $result = true;
                break;
            }
        }

        return $result;
    }
}
