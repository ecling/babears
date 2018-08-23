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
class Adyen_Payment_Model_Adyen_Data_InvoiceRow extends Adyen_Payment_Model_Adyen_Data_Abstract {

    public $currency;
    public $description;
    public $itemPrice;
    public $itemVAT;
    public $lineReference;
    public $numberOfItems;
    public $vatCategory;

    public function create($item, $count, $order) {
        $currency = $order->getOrderCurrencyCode();
        $this->currency = $currency;
        $this->description = $item->getName();
        $this->itemPrice = Mage::helper('adyen')->formatAmount($item->getPrice(), $currency);
        $this->itemVAT = ($item->getTaxAmount()>0 && $item->getPriceInclTax()>0)?
            Mage::helper('adyen')->formatAmount($item->getPriceInclTax(), $currency) -
            Mage::helper('adyen')->formatAmount($item->getPrice(), $currency):
            Mage::helper('adyen')->formatAmount($item->getTaxAmount(), $currency);
        $this->lineReference = $count;
        $this->numberOfItems = (int) $item->getQtyOrdered();
        $this->vatCategory = "None";
        return $this;
    }

}