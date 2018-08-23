<?php

/**
 * Class CheckoutApi_ChargePayment_Model_Observer
 *
 * @version 20151203
 */
class CheckoutApi_ChargePayment_Model_Observer {

    /**
     * Cancel Order after Void
     *
     * @param $observer
     * @return CheckoutApi_ChargePayment_Model_Observer
     * @throws Exception
     *
     * @version 20151203
     */
    public function setOrderStatusForVoid(Varien_Event_Observer $observer) {
        $orderId            = Mage::app()->getRequest()->getParam('order_id');
        $order              = Mage::getModel('sales/order')->load($orderId);

        if (!is_object($order)) {
            return $this;
        }
        $payment            = $order->getPayment();
        $paymentCode        = (string)$payment->getMethodInstance()->getCode();
        $isCancelledOrder   = false;

        switch ($paymentCode) {
            case CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD:
                $isCancelledOrder = Mage::getModel('chargepayment/creditCard')->getVoidStatus();
                break;
            case CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_KIT:
                $isCancelledOrder = Mage::getModel('chargepayment/creditCardKit')->getVoidStatus();
                break;
            case CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_JS:
                $isCancelledOrder = Mage::getModel('chargepayment/creditCardJs')->getVoidStatus();
                break;
            case CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_HOSTED:
                $isCancelledOrder = Mage::getModel('chargepayment/hosted')->getVoidStatus();
                break;
        }

        if (!$isCancelledOrder) {
            return;
        }

        $message    = 'Transaction has been void';

        $order->registerCancellation($message);
        $order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
        $order->save();

        return $this;
    }

    /**
     * Save order into registry to use it in the overloaded controller.
     *
     * @param Varien_Event_Observer $observer
     * @return CheckoutApi_ChargePayment_Model_Observer
     *
     * @version 20160215
     *
     */
    public function saveOrderAfterSubmit(Varien_Event_Observer $observer)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getData('order');
        Mage::register('charge_payment_order', $order, true);

        return $this;
    }
}