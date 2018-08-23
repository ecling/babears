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

/**
 * Installments manipulation helper
 */
class Adyen_Payment_Helper_Installments
{
    /**
     * Retrieve fixed qty value
     *
     * @param mixed $qty
     * @return float|null
     */
    protected function _fixQty($qty)
    {
        return (!empty($qty) ? (float)$qty : null);
    }

    /**
     * Generate a storable representation of a value
     *
     * @param mixed $value
     * @return string
     */
    protected function _serializeValue($value)
    {
    	return serialize($value);
    }

    /**
     * Create a value from a storable representation
     *
     * @param mixed $value
     * @return array
     */
    protected function _unserializeValue($value)
    {
        if (is_string($value) && !empty($value)) {
            return unserialize($value);
        } else {
            return array();
        }
    }

    /**
     * Check whether value is in form retrieved by _encodeArrayFieldValue()
     *
     * @param mixed
     * @return bool
     */
    protected function _isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        unset($value['__empty']);
        foreach ($value as $_id => $row) {
            if (!is_array($row) || !array_key_exists('installment_currency',$row) || !array_key_exists('installment_boundary', $row) || !array_key_exists('installment_frequency', $row ) || !array_key_exists('installment_interest', $row )) {
                return false;
            }
        }
        return true;
    }

    public function getInstallments($store = null, $ccType = "installments") {
        $value = Mage::getStoreConfig("payment/adyen_cc/".$ccType, $store);
        $value = $this->_unserializeValue($value);
        return $value;
    }

    /**
     * Encode value to be used in Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     * deserialized DB entry => HTML form
     * @param array
     * @return array
     */
    protected function _encodeArrayFieldValue(array $value)
    {
        $result = array();
        foreach ($value as $triplet){

            $currency = (isset($triplet[0])) ? $triplet[0] : "";
            $boundary = (isset($triplet[1])) ? $triplet[1] : "";
            $frequency = (isset($triplet[2])) ? $triplet[2] : "";
            $interest = (isset($triplet[3])) ? $triplet[3] : "";

            $_id = Mage::helper('core')->uniqHash('_');
            $result[$_id] = array(
            	'installment_currency' => $currency,
                'installment_boundary' => $boundary,
                'installment_frequency' => $frequency,
                'installment_interest' => $interest
            );
        }
        return $result;
    }

    /**
     * Decode value from used in Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     * HTML form => deserialized DB entry
     * @param array
     * @return array
     */
    protected function _decodeArrayFieldValue(array $value)
    {
        $result = array();
        unset($value['__empty']);
        foreach ($value as $_id => $row) {
            if (!is_array($row) || !array_key_exists('installment_currency',$row) || !array_key_exists('installment_boundary', $row) || !array_key_exists('installment_frequency', $row) || !array_key_exists('installment_interest', $row)) {
                continue;
            }
            $currency = $row['installment_currency'];
            $boundary = $row['installment_boundary'];
            $frequency = $row['installment_frequency'];
            $interest = $row['installment_interest'];
            $result[] = array($currency,$boundary,$frequency,$interest);
        }
        return $result;
    }

    /**
     * Retrieve maximum number for installments for given amount with config
     *
     * @param int $customerGroupId
     * @param mixed $store
     * @return float|null
     */
    public function getConfigValue($curr,$amount, $store = null, $ccType = "installments")
    {
        $value = $this->getInstallments($store, $ccType);

        if ($this->_isEncodedArrayFieldValue($value)) {
            $value = $this->_decodeArrayFieldValue($value);
        }
        $cur_minimal_boundary = -1;
        $resulting_freq = 1;
        foreach ($value as $row) {
        	list($currency,$boundary,$frequency) = $row;
            if ($curr == $currency){
            	if($amount <= $boundary && ($boundary <= $cur_minimal_boundary || $cur_minimal_boundary == -1) ) {
                    $cur_minimal_boundary = $boundary;
	            	$resulting_freq = $frequency;
	            }
	            if($boundary == "" && $cur_minimal_boundary == -1){
	            	$resulting_freq = $frequency;
	            }
            }
           
        }
        return $resulting_freq;
    }
    
    public function isInstallmentsEnabled($store = null){
    	$value = Mage::getStoreConfig("payment/adyen_cc/enable_installments", $store);
    	return $value;
    }



    public function getInstallmentForCreditCardType($ccType) {

        // retrieving quote
        $quote = (Mage::getModel('checkout/type_onepage') !== false)? Mage::getModel('checkout/type_onepage')->getQuote(): Mage::getModel('checkout/session')->getQuote();

        $currency = $quote->getQuoteCurrencyCode();

        if($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }

        // distract the already included added fee for installment you selected before
        if($address->getBasePaymentInstallmentFeeAmount() > 0) {
            $amount = (double) ($quote->getGrandTotal() - $address->getBasePaymentInstallmentFeeAmount());
        } else {
            $amount = (double) $quote->getGrandTotal();
        }

        // installment key where installents are saved in settings
        $ccTypeInstallments = "installments_".$ccType;

        // check if this type has installments configured
        $all_installments = $this->getInstallments(null, $ccTypeInstallments);

        if(empty($all_installments)) {
            // no installments congigure fall back on default
            $ccTypeInstallments = null;
        } else {
            $max_installments = $this->getConfigValue($currency,$amount, null, $ccTypeInstallments);
        }

        // Fallback to the default installments if creditcard type has no one configured
        if($ccTypeInstallments == null) {
            $max_installments = $this->getConfigValue($currency,$amount, null);
            $all_installments = $this->getInstallments();
        }

        // result array here
        for($i=1;$i<=$max_installments;$i++){

            // check if installment has extra interest
            $key = $i-1;
            $installment = $all_installments[$key];
            if(isset($installment[3]) && $installment[3] > 0) {
                $total_amount_with_interest = $amount + ($amount * ($installment[3] / 100));
            } else {
                $total_amount_with_interest = $amount;
            }

            $partial_amount = ((double)$total_amount_with_interest)/$i;
            $result[(string)$i] = $i."x ".$currency." ".number_format($partial_amount,2);
        }
        return $result;
    }





    /**
     * Make value readable by Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     *
     * @param mixed $value
     * @return array
     */
    public function makeArrayFieldValue($value)
    {
        $value = $this->_unserializeValue($value);
        if (!$this->_isEncodedArrayFieldValue($value)) {
            $value = $this->_encodeArrayFieldValue($value);
        }
        return $value;
    }

    /**
     * Make value ready for store
     *
     * @param mixed $value
     * @return string
     */
    public function makeStorableArrayFieldValue($value)
    {
        if ($this->_isEncodedArrayFieldValue($value)) {
            $value = $this->_decodeArrayFieldValue($value);
        }
        $value = $this->_serializeValue($value);
        return $value;
    }
}
