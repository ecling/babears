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
class Adyen_Payment_Block_PosExpressCheckout extends Mage_Core_Block_Template
{

    public function hasExpressCheckout()
    {
        // must be login to show this checkout option
        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            return (string) Mage::helper('adyen')->hasExpressCheckout();
        } else {
            return false;
        }
    }

    public function getExpressCheckoutTitle() {
        return Mage::helper('adyen')->getConfigData("title", "adyen_pos", null);
    }

    public function getEmailAddressShopper() {
        return Mage::getSingleton('checkout/session')->getAdyenEmailShopper();
    }

    public function hasCashExpressCheckout()
    {
        // must be login to show this checkout option
        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            return (string) Mage::helper('adyen')->hasCashExpressCheckout();
        } else {
            return false;
        }
    }

    public function getCashExpressCheckoutTitle() {
        return Mage::helper('adyen')->getConfigData("title", "adyen_cash", null);
    }

    public function inKioskMode()
    {
        return Mage::helper('adyen')->getConfigData("express_checkout_kiosk_mode", "adyen_pos", null);
    }

    public function showExpressCheckoutRecurringCards() {
        return Mage::helper('adyen')->getConfigData("express_checkout_recurring", "adyen_pos", null);
    }

    public function enabledCashDrawer() {
        return Mage::helper('adyen')->getConfigData("cash_drawer", "adyen_cash", null);
    }

    public function getCashDrawerPrinterIp() {
        return Mage::helper('adyen')->getConfigData("cash_drawer_printer_ip", "adyen_cash", null);
    }

    public function getCashDrawerPrinterPort() {
        return Mage::helper('adyen')->_getConfigData("cash_drawer_printer_port", "adyen_pos", null);
    }

    public function getCashDrawerPrinterDeviceId() {
        return Mage::helper('adyen')->_getConfigData("cash_drawer_printer_device_id", "adyen_pos", null);
    }

}