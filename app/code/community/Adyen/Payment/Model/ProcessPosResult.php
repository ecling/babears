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
class Adyen_Payment_Model_ProcessPosResult extends Mage_Core_Model_Abstract {

    /**
     * Collected debug information
     *
     * @var array
     */
    protected $_debugData = array();

    public function processPosResponse($params)
    {
        $storeId = null;
        $returnResult = false;

        $this->_debugData['processPosResponse begin'] = 'Begin to process POS result url';

        $helper = Mage::helper('adyen');

        $this->_debugData['POS Response'] = $params;


        $actionName = $this->_getRequest()->getActionName();
        $result = $params['result'];

        // check if result comes from POS device comes from POS and validate Checksum
        if($actionName == "successPos" && $result != "" && $this->_validateChecksum($params)) {

            //get order && payment objects
            $order = Mage::getModel('sales/order');
            $incrementId = $params['originalCustomMerchantReference'];

            if($incrementId) {
                $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
                if ($order->getId()) {

                    // set StoreId for retrieving debug log setting
                    $storeId = $order->getStoreId();

                    if($result == 'APPROVED') {

                        $this->_debugData['processPosResponse'] = 'Result is APPROVED';

                        // set adyen event status on true
                        $order->setAdyenEventCode(Adyen_Payment_Model_Event::ADYEN_EVENT_POSAPPROVED);

                        $comment = Mage::helper('adyen')
                            ->__('%s <br /> Result: %s <br /> paymentMethod: %s', 'Adyen App Result URL Notification:', $result, 'POS');

                        $order->addStatusHistoryComment($comment, false);

                        try {
                            $order->save();
                            $returnResult = true;
                            $this->_debugData['complete'] = 'Order is updated with AdyenEventCode: ' . $order->getAdyenEventCode();
                        } catch (Exception $e) {
                            $this->_debugData['error'] = 'error updating order reason: ' . $e->getMessage();
                            Mage::logException($e);
                        }
                    } else {

                        $this->_debugData['processPosResponse'] = 'Result is: ' . $result;

                        $comment = Mage::helper('adyen')
                            ->__('%s <br /> Result: %s <br /> paymentMethod: %s', 'Adyen App Result URL Notification:', $result, 'POS');

                        $order->addStatusHistoryComment($comment, Mage_Sales_Model_Order::STATE_CANCELED);

                        $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, true);

                        if (!$order->canCancel()) {
                            $order->addStatusHistoryComment($helper->__('Order can not be canceled'), Mage_Sales_Model_Order::STATE_CANCELED);
                            $order->save();
                            $this->_debugData['error'] = 'can not be canceled';

                        } else {
                            $order->cancel()->save();
                            $this->_debugData['complete'] = 'Order is cancelled';
                        }
                    }
                } else {
                    $this->_debugData['error'] = 'Order does not exists with increment_id: ' . $incrementId;
                }
            } else {
                $this->_debugData['error'] = 'Empty merchantReference';
            }
        } else {
            $this->_debugData['error'] = 'actionName or checksum failed or response is empty';
        }

        $this->_debug($storeId);

        return $returnResult;
    }

    protected function _validateChecksum($params)
    {
        $checksum = $params['cs'];
        $result = $params['result'];
        $amount = $params['originalCustomAmount'];
        $currency = $params['originalCustomCurrency'];
        $sessionId = $params['sessionId'];


        // for android sessionis is with low i
        if($sessionId == "") {
            $sessionId = $params['sessionid'];
        }

        // calculate amount checksum
        $amount_checksum = 0;

        $amountLength = strlen($amount);
        for($i=0;$i<$amountLength;$i++)
        {
            // ASCII value use ord
            $checksumCalc = ord($amount[$i]) - 48;
            $amount_checksum += $checksumCalc;
        }

        $currency_checksum = 0;
        $currencyLength = strlen($currency);
        for($i=0;$i<$currencyLength;$i++)
        {
            $checksumCalc = ord($currency[$i]) - 64;
            $currency_checksum += $checksumCalc;
        }

        $result_checksum = 0;
        $resultLength = strlen($result);
        for($i=0;$i<$resultLength;$i++)
        {
            $checksumCalc = ord($result[$i]) - 64;
            $result_checksum += $checksumCalc;
        }

        $sessionId_checksum = 0;
        $sessionIdLength = strlen($sessionId);
        for($i=0;$i<$sessionIdLength;$i++)
        {
            $checksumCalc = $this->_getAscii2Int($sessionId[$i]);
            $sessionId_checksum += $checksumCalc;
        }

        $total_result_checksum = (($amount_checksum + $currency_checksum + $result_checksum) * $sessionId_checksum) % 100;

        // check if request is valid
        if($total_result_checksum == $checksum) {
            $this->_debugData['_validateChecksum'] = 'Checksum is valid';
            return true;
        }
        $this->_debugData['_validateChecksum'] = 'Checksum is invalid!';
        return false;
    }

    protected function _getRequest()
    {
        return Mage::app()->getRequest();
    }

    protected function _getAscii2Int($ascii)
    {
        if (is_numeric($ascii)){
            $int = ord($ascii) - 48;
        } else {
            $int = ord($ascii) - 64;
        }
        return $int;
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
            $file = 'adyen_result_pos.log';
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