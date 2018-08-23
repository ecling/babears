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
class Adyen_Fee_Model_Total_PaymentFee_Creditmemo extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {

        /**
         * Only option to receive the payment fee entered to modify the creditMemo refund fee.
         */
        $order = $creditmemo->getOrder();
        $data = $this->_getRequest('creditmemo');

        // add Refunded amount to refunded
        $basePaymentFeeAmountRefunded = $order->getPayment()->getAdditionalInformation("base_payment_fee_amount_refunded");
        $paymentFeeAmountRefunded = $order->getPayment()->getAdditionalInformation("payment_fee_amount_refunded");

        $isPaymentFeeInclTax = Mage::getSingleton('adyen_fee/tax_config')->displaySalesPaymentFeeInclTax($order->getStoreId());

        if ($this->_isLoggedIn()) {
            if ($data) {
                if (isset($data['adyen_fee_payment_fee_refund'])) {
                    if ($data['adyen_fee_payment_fee_refund'] == '') {
                        $data['adyen_fee_payment_fee_refund'] = 0;
                    }

                    $store = $order->getStore();
                    $refundAmount = $data['adyen_fee_payment_fee_refund'];
                    $baseRefundAmount = $store->convertPrice($refundAmount, false);

                    // if refundAmount is set to empty set amount to zero
                    if($refundAmount == 0 || $refundAmount == "") {

                        $creditmemo->setPaymentFeeAmount(0);
                        $creditmemo->setPaymentFeeTax(0);
                        $creditmemo->setBasePaymentFeeAmount(0);
                        $creditmemo->setBasePaymentFeeTax(0);

                        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $creditmemo->getPaymentFeeAmount() + $creditmemo->getPaymentFeeTax());
                        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $creditmemo->getBasePaymentFeeAmount() + $creditmemo->getBasePaymentFeeTax());

                        return $this;
                    }

                    /*
                     * if payment fee is incl tax remove the tax from the amount
                     */
                    if($isPaymentFeeInclTax && $refundAmount > 0) {

                        $refundAmountInclTax = $refundAmount;
                        $baseRefundAmountInclTax = $baseRefundAmount;

                        // calculate tax

                        // get base tax rate
                        $basePaymentFeeAmount = Mage::app()->getStore()->roundPrice($order->getBasePaymentFeeAmount());
                        $basePaymentFeeTax = Mage::app()->getStore()->roundPrice($order->getBasePaymentFeeTax());

                        // get tax rate
                        $paymentFeeAmount = Mage::app()->getStore()->roundPrice($order->getPaymentFeeAmount());
                        $paymentFeeTax = Mage::app()->getStore()->roundPrice($order->getPaymentFeeTax());

                        // tax rate
                        $baseRate = $basePaymentFeeTax / $basePaymentFeeAmount;
                        // rate is already in the amount so + 1 because example 21% = 121% = 1.21
                        $baseRate = $baseRate + 1;

                        $rate = $paymentFeeTax / $paymentFeeAmount;

                        // rate is already in the amount so + 1 because example 21% = 121% = 1.21
                        $rate = $rate + 1;

                        // tax amount
                        $baseRefundAmount = $baseRefundAmountInclTax / $baseRate;
                        $refundAmount = $refundAmountInclTax / $rate;

                        $baseRefundAmount = $store->convertPrice($baseRefundAmount, false);

                        // tax amount
                        $taxAmount = $refundAmountInclTax - $refundAmount;
                        $baseTaxAmount = $baseRefundAmountInclTax - $baseRefundAmount;

                        $creditmemo->setPaymentFeeAmount($refundAmount);
                        $creditmemo->setBasePaymentFeeAmount($baseRefundAmount);

                        $creditmemo->setGrandTotal($creditmemo->getGrandTotal()+$creditmemo->getPaymentFeeAmount() + $taxAmount);
                        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal()+$creditmemo->getBasePaymentFeeAmount() + $baseTaxAmount);

                    } else {

                        // set amount
                        $creditmemo->setPaymentFeeAmount($refundAmount);
                        $baseRefundAmount = $store->convertPrice($refundAmount, false);
                        $creditmemo->setBasePaymentFeeAmount($baseRefundAmount);

                        // get base tax rate
                        $basePaymentFeeAmount = Mage::app()->getStore()->roundPrice($order->getBasePaymentFeeAmount());
                        $basePaymentFeeTax = Mage::app()->getStore()->roundPrice($order->getBasePaymentFeeTax());

                        // get tax rate
                        $paymentFeeAmount = Mage::app()->getStore()->roundPrice($order->getPaymentFeeAmount());
                        $paymentFeeTax = Mage::app()->getStore()->roundPrice($order->getPaymentFeeTax());

                        // tax rate
                        $baseRate = $basePaymentFeeTax / $basePaymentFeeAmount;
                        $rate = $paymentFeeTax / $paymentFeeAmount;

                        // tax amount
                        $baseTaxAmount = $refundAmount * $baseRate;
                        $taxAmount = $refundAmount * $rate;

                        $creditmemo->setGrandTotal($creditmemo->getGrandTotal()+$creditmemo->getPaymentFeeAmount()+$taxAmount);
                        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal()+$creditmemo->getBasePaymentFeeAmount()+$baseTaxAmount);

                    }

                    // add fee to refunded variable so it can be substracten from open amount
                    if ($basePaymentFeeAmountRefunded > 0) {
                        $order->getPayment()->setAdditionalInformation("base_payment_fee_amount_refunded", $basePaymentFeeAmountRefunded + $baseRefundAmount);
                    } else {
                        $order->getPayment()->setAdditionalInformation("base_payment_fee_amount_refunded", $baseRefundAmount);
                    }

                    if($paymentFeeAmountRefunded > 0) {
                        $order->getPayment()->setAdditionalInformation("payment_fee_amount_refunded", $paymentFeeAmountRefunded + $refundAmount);
                    } else {
                        $order->getPayment()->setAdditionalInformation("payment_fee_amount_refunded", $refundAmount);
                    }

                    // do not do other logic
                    return $this;
                }
            }
        }

        // Substract the already refunded amount off the GrandTotal and paymentFee amount.
        if($basePaymentFeeAmountRefunded > 0) {
            
            $allowedRefundedAmount = $creditmemo->getPaymentFeeAmount()-$paymentFeeAmountRefunded;
            $allowedBasePaymentFeeAmount = $creditmemo->getBasePaymentFeeAmount()-$basePaymentFeeAmountRefunded;

            $creditmemo->setPaymentFeeAmount($allowedRefundedAmount);
            $creditmemo->setBasePaymentFeeAmount($allowedBasePaymentFeeAmount);


            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $allowedRefundedAmount + $creditmemo->getPaymentFeeTax());
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $allowedBasePaymentFeeAmount + $creditmemo->getBasePaymentFeeTax());


        } else {
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $creditmemo->getPaymentFeeAmount() + $creditmemo->getPaymentFeeTax());
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $creditmemo->getBasePaymentFeeAmount() + $creditmemo->getBasePaymentFeeTax());
        }

        return $this;
    }

    protected function _isLoggedIn()
    {
        return Mage::getSingleton('admin/session')->isLoggedIn();
    }

    protected function _getRequest($param)
    {
        return Mage::app()->getRequest()->getParam($param);
    }
}