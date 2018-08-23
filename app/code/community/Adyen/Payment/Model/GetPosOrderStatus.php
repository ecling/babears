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
class Adyen_Payment_Model_GetPosOrderStatus extends Mage_Core_Model_Abstract {

    /**
     * Collected debug information
     *
     * @var array
     */
    protected $_debugData = array();

    public function hasApprovedOrderStatus($merchantReference, $count = 0)
    {
        $storeId = null;

        if($count == 0) {
            $this->_debugData['getOrderStatus begin'] = 'Check the order status';
        } else {
            $this->_debugData['getOrderStatus count: '.$count] = 'Check the order status';
        }

        if($merchantReference != "") {

            $this->_debugData['getOrderStatus count: '.$count . ' reference'] = 'MerchantReference is ' . $merchantReference;

            // get the order
            $order = Mage::getModel('sales/order')->loadByIncrementId($merchantReference);

            $storeId = $order->getStoreId();

            $result = $this->_checkOrderStatus($order, 0);

            if($result) {
                $this->_debugData['getOrderStatus end'] = 'getOrderStatus result is true';
                $this->_debug($storeId);
                return true;
            }
        } else {
            $this->_debugData['error'] = 'order has no merchantReference';
        }

        $this->_debugData['getOrderStatus end'] = 'getOrderStatus result is false';
        $this->_debug($storeId);
        return false;
    }

    protected function _checkOrderStatus($order, $count = 0)
    {

        // if order is not cancelled then order is success
        if($order->getStatus() == Mage_Sales_Model_Order::STATE_CANCELED || $order->getStatus() == Mage_Sales_Model_Order::STATE_HOLDED) {
            $this->_debugData['getOrderStatus count: '.$count . ' cancelled'] = 'order has the status cancel or holded';
            return false;
        } else if($order->getStatus() == Mage_Sales_Model_Order::STATE_PROCESSING || $order->getAdyenEventCode() == Adyen_Payment_Model_Event::ADYEN_EVENT_POSAPPROVED || substr($order->getAdyenEventCode(), 0, 13)  == Adyen_Payment_Model_Event::ADYEN_EVENT_AUTHORISATION)
        {
            $this->_debugData['getOrderStatus count: '.$count . ' success'] = 'order has the status: '.$order->getStatus();
            return true;
        } else if($order->getStatus() == 'pending' &&  $order->getAdyenEventCode() == "")
        {
            $this->_debugData['getOrderStatus count: '.$count . ' pending'] = 'order has the status: '.$order->getStatus() . ' lets wait a second';

            sleep(2);
            ++$count;

            if($count > 5) {
                $this->_debugData['getOrderStatus count: '.$count . ' end'] = 'order has the status: '.$order->getStatus() . ' this is the third try so cancel the order';
                return false;
            }

            $this->_debugData['getOrderStatus count: '.$count . 'retry'] = 'Let\'s try again';
            // load the order again and check if status has changed
            $order = Mage::getModel('sales/order')->loadByIncrementId($order->getIncrementId());
            return $this->_checkOrderStatus($order, $count);
        } else {

            $this->_debugData['getOrderStatus count: '.$count . ' pending'] = 'order has the status: '.$order->getStatus() . ' lets wait a second';

            sleep(2);
            ++$count;

            if($count > 5) {
                $this->_debugData['getOrderStatus count: '.$count . ' end'] = 'order has the status: '.$order->getStatus() . ' this is the third try so cancel the order';
                return false;
            }
            $this->_debugData['getOrderStatus count: '.$count . 'retry'] = 'Let\'s try again';
            // load the order again and check if status has changed
            $order = Mage::getModel('sales/order')->loadByIncrementId($order->getIncrementId());
            return $this->_checkOrderStatus($order,$count);
        }
    }

    /**
     * Log debug data to file
     *
     * @param $storeId
     * @param mixed $debugData
     */
    protected function _debug($storeId)
    {
        if ($this->_getConfigData('debug', 'adyen_abstract', $storeId)) {
            $file = 'adyen_orderstatus_pos.log';
            Mage::getModel('core/log_adapter', $file)->log($this->_debugData);
        }
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
