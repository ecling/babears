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
class Adyen_Fee_Model_Sales_Quote_Address_Total_PaymentPercentageFee extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $_code = 'payment_percentage_fee';

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        // Makes sure you only use the address type shipping
        $items = $this->_getAddressItems($address);
        if (!count($items)) {
            return $this;
        }

        // reset totals by default (needed for onestepcheckout module)
        $address->setPaymentPercentageFee(0);
        $address->setBasePaymentPercentageFee(0);

        $quote = $address->getQuote();
        $adyenFeeHelper = Mage::helper('adyen_fee');
        $paymentMethod = $quote->getPayment()->getMethod();
        $percentageFee = $adyenFeeHelper->getHppPaymentMethodPercentageFee($paymentMethod);

        if(!$percentageFee) {
            return $this;
        }

        $grandTotal = $address->getGrandTotal();
        $baseGrandTotal = $address->getBaseGrandTotal();
        $fee = ($baseGrandTotal / 100) * $percentageFee;

        $address->setPaymentPercentageFee($address->getQuote()->getStore()->convertPrice($fee));
        $address->setBasePaymentPercentageFee($fee);

        $address->setGrandTotal($grandTotal + $address->getPaymentPercentageFee());
        $address->setBaseGrandTotal($baseGrandTotal + $address->getBasePaymentPercentageFee());
        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amt = $address->getPaymentPercentageFee();

        if ($amt != 0) {
            $address->addTotal(array(
                'code'=>$this->getCode(),
                'title'=> Mage::helper('adyen')->__('Payment Percentage Fee'),
                'value'=> $amt
            ));
        } else {
            Mage::helper('adyen_fee')->removeTotal($address, $this->getCode());
        }

        return $this;
    }
}