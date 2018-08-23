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
class Adyen_Fee_Model_Total_PaymentFee_Tax_Creditmemo extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        //recalculate tax amounts in case if refund shipping value was changed
        if ((float) $creditmemo->getBasePaymentFeeAmount() > 0 && (float) $order->getBasePaymentFeeAmount() > 0) {
            $taxFactor = $creditmemo->getBasePaymentFeeAmount()/$order->getBasePaymentFeeAmount();
            $paymentFeeTax = $creditmemo->getPaymentFeeTax() * $taxFactor;
            $paymentBaseFeeTax = $creditmemo->getBasePaymentFeeTax() * $taxFactor;
        } else {
            $paymentFeeTax = $creditmemo->getPaymentFeeTax();
            $paymentBaseFeeTax = $creditmemo->getBasePaymentFeeTax();
        }

        // set the tax fee
        $creditmemo->setPaymentFeeTax($paymentFeeTax);
        $creditmemo->setBasePaymentFeeTax($paymentBaseFeeTax);

        // use the tax fee to calculate total tax amount
        $creditmemo->setTaxAmount($creditmemo->getTaxAmount()+$creditmemo->getPaymentFeeTax());
        $creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount()+$creditmemo->getBasePaymentFeeTax());

        return $this;
    }
}