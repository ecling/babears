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
class Adyen_Fee_Helper_Data extends Mage_Payment_Helper_Data
{

    /**
     * payment fee for adyen hpp payment method
     */
    const XML_PATH_HPP_PAYMENT_METHOD_FEE   = 'payment/adyen_hpp/fee';

    /**
     * Check if payment method is enabled
     *
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    public function isPaymentFeeEnabled(Mage_Sales_Model_Quote $quote)
    {
        $paymentMethod = $quote->getPayment()->getMethod();

        if($paymentMethod == 'adyen_openinvoice')
        {
            $fee = Mage::getStoreConfig('payment/adyen_openinvoice/fee');
            if($fee) {
                return true;
            }
        } elseif($paymentMethod == 'adyen_ideal') {
            $fee = Mage::getStoreConfig('payment/adyen_ideal/fee');
            if($fee) {
                return true;
            }
        } elseif(substr($paymentMethod,0, 10)  == 'adyen_hpp_') {

            $fee = $this->getHppPaymentMethodFee($paymentMethod);
            if($fee) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the payment fee amount of the payment method that is selected
     *
     * @param Mage_Sales_Model_Quote
     * @return float
     */
    public function getPaymentFeeAmount(Mage_Sales_Model_Quote $quote, $store = null)
    {
        $paymentMethod = $quote->getPayment()->getMethod();
        if ($paymentMethod == 'adyen_openinvoice') {
            return Mage::getStoreConfig('payment/adyen_openinvoice/fee');
        } elseif($paymentMethod == 'adyen_ideal') {
            return Mage::getStoreConfig('payment/adyen_ideal/fee');
        } elseif(substr($paymentMethod,0, 10)  == 'adyen_hpp_') {
            return $this->getHppPaymentMethodFee($paymentMethod);
        }
        return 0;
    }

    /**
     * retrieve Adyen HPP payment method fee setup
     *
     * @return array
     */
    public function getHppPaymentMethodFees()
    {
        $config = Mage::getStoreConfig(self::XML_PATH_HPP_PAYMENT_METHOD_FEE);

        return $config ? unserialize($config) : array();
    }

    /**
     * Get the fixed payment method fee amount for the payment method that is selected
     *
     * @param $paymentMethod
     * @return paymentFee
     */
    public function getHppPaymentMethodFee($paymentMethod)
    {
        $paymentMethod = str_replace('adyen_hpp_', '', $paymentMethod);

        $paymentFees = $this->getHppPaymentMethodFees();

        if($paymentFees && is_array($paymentFees) && !empty($paymentFees)) {

            foreach($paymentFees as $paymentFee) {
                if(isset($paymentFee['code']) && $paymentFee['code'] == $paymentMethod) {
                    if(isset($paymentFee['amount'])) {
                        return $paymentFee['amount'];
                    }
                }
            }
        }
        return null;
    }

    /**
     * Get the payment method fee percentage for the payment method that is selected
     *
     * @param $paymentMethod
     * @return paymentFee
     */
    public function getHppPaymentMethodPercentageFee($paymentMethod)
    {
        $paymentMethod = str_replace('adyen_hpp_', '', $paymentMethod);
        $paymentFees = $this->getHppPaymentMethodFees();

        if($paymentFees && is_array($paymentFees) && !empty($paymentFees)) {

            foreach($paymentFees as $paymentFee) {
                if(isset($paymentFee['code']) && $paymentFee['code'] == $paymentMethod) {
                    if(isset($paymentFee['percentage']) && $paymentFee['percentage']) {
                        return $paymentFee['percentage'];
                    }
                }
            }
        }
        return null;
    }

    /**
     * Return Payment Fee Exclusive tax
     *
     * @param $address
     * @return float
     */
    public function getPaymentFeeExclVat($address)
    {
        $config = Mage::getSingleton('adyen_fee/tax_config');
        $quote = $address->getQuote();
        $store = $quote->getStore();
        $fee = $this->getPaymentFeeAmount($quote, $store);
        if ($fee && $config->paymentFeePriceIncludesTax($store)) {
            $fee -= $this->getPaymentFeeVat($address);
        }
        return $fee;
    }

    /**
     * Returns the payment fee tax for the payment fee
     *
     * @param Mage_Sales_Model_Quote_Address $shippingAddress
     * @return float
     */
    public function getPaymentFeeVat($shippingAddress)
    {
        $paymentTax = 0;
        $quote = $shippingAddress->getQuote();
        $store = $quote->getStore();
        $fee = $this->getPaymentFeeAmount($quote, $store);

        if ($fee) {
            $config = Mage::getSingleton('adyen_fee/tax_config');
            $custTaxClassId = $quote->getCustomerTaxClassId();
            $taxCalculationModel = Mage::getSingleton('tax/calculation');
            $request = $taxCalculationModel->getRateRequest($shippingAddress, $quote->getBillingAddress(), $custTaxClassId, $store);
            $paymentTaxClass = $config->getPaymentFeeTaxClass($store);
            $rate = $taxCalculationModel->getRate($request->setProductClassId($paymentTaxClass));
            if ($rate) {
                $paymentTax = $taxCalculationModel->calcTaxAmount($fee, $rate, $config->paymentFeePriceIncludesTax($store), true);
            }
        }
        return $paymentTax;
    }

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     * @param string $code
     */
    public function removeTotal(Mage_Sales_Model_Quote_Address $address, $code)
    {
        $reflectedClass = new ReflectionClass($address);
        $propertyTotals = $reflectedClass->getProperty('_totals');
        $propertyTotals->setAccessible(true);
        $totals = $propertyTotals->getValue($address);
        unset($totals[$code]);
        $propertyTotals->setValue($address, $totals);
    }

}