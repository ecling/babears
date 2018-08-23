<?php

require_once Mage::getModuleDir('controllers', 'Mage_Adminhtml'). DS. 'Sales' . DS . 'Order' . DS . 'CreateController.php';

/**
 * Controller for Checkout.com Webhooks
 *
 * Class CheckoutApi_ChargePayment_ApiController
 *
 * @version 20160215
 */
class CheckoutApi_ChargePayment_Adminhtml_CheckoutApi_ChargePayment_ApiController extends Mage_Adminhtml_Sales_Order_CreateController
{
    /**
     * Place order
     *
     * @version 20160216
     */
    public function placeAction() {
        $paymentParam = $this->getRequest()->getParam('payment');
        $this->getRequest()->setPost('collect_shipping_rates', 1);
        $this->_processActionData('save');

        $route  = 'adminhtml/sales_order_create';
        $params = array();

        //get confirmation by email flag
        $orderData = $this->getRequest()->getPost('order');

        if (isset($paymentParam['method'])) {
            //create order partially
            $this->_getOrderCreateModel()->setPaymentData($paymentParam);
            $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentParam);

            $orderData['send_confirmation'] = 0;
            $this->getRequest()->setPost('order', $orderData);

            try {
                //do not cancel old order.
                $oldOrder = $this->_getOrderCreateModel()->getSession()->getOrder();
                $oldOrder->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, false);

                $order = $this->_getOrderCreateModel()
                    ->setIsValidate(true)
                    ->importPostData($this->getRequest()->getPost('order'))
                    ->createOrder();

                $payment        = $order->getPayment();
                $paymentToken   = $payment->getAdditionalInformation('payment_token');

                $isError        = true;

                if (empty($paymentToken)) {
                    $isError = false;

                    Mage::getSingleton('adminhtml/session')->clear();
                    Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));

                    $params['order_id'] = $order->getId();
                    $route = 'adminhtml/sales_order/view';
                }
            }
            catch (Mage_Core_Exception $e) {
                $message = $e->getMessage();
                if( !empty($message) ) {
                    $this->_getSession()->addError($message);
                }
                $isError = true;
            }
            catch (Exception $e) {
                $this->_getSession()->addException($e, $this->__('Order saving error: %s', $e->getMessage()));
                $isError = true;
            }

            if ($isError && isset($order) && is_object($order)) {
                $session = Mage::getSingleton('chargepayment/session_quote');
                $session->addCheckoutOrderIncrementId($order->getIncrementId());
                $session->setAdminTokenRedirectUrl($payment->getAdditionalInformation('payment_token_url'));
                $session->setLastAdminOrderId($order->getId());

                $order->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
                $order->save();

                $route = 'adminhtml/sales_order_create';
            }

            $this->_redirect($route, $params);
        }
        else {
            Mage::getSingleton('adminhtml/session')->clear();
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please, choose payment method'));

            $this->_redirect($route);
        }
    }

    /**
     * Verify charge by payment token
     *
     * @version 20160218
     */
    public function verifyAction() {
        $responseToken  = (string)$this->getRequest()->getParam('cko-payment-token');

        if (!$responseToken) {
            return;
        }

        $result             = Mage::getModel('chargepayment/webhook')->authorizeByPaymentToken($responseToken);
        $error              = $result['error'];
        $orderIncrementId   = $result['order_increment_id'];
        $params             = array();

        if (!$error && !is_null($orderIncrementId)) {
            Mage::getSingleton('adminhtml/session')->clear();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The payment token verify successes.'));

            $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
            $params['order_id'] = $order->getId();
            $route = 'adminhtml/sales_order/view';
        } else {
            Mage::getSingleton('adminhtml/session')->clear();
            Mage::getSingleton('adminhtml/session')->addError($this->__('Error verify payment token'));

            $route = 'adminhtml/sales_order_create';
        }

        $this->_redirect($route, $params);

        return;
    }
}
