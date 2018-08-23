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
 
class Adyen_Payment_Helper_Billing_Agreement extends Mage_Core_Helper_Abstract
{
    
    /**
     * @return Mage_Customer_Model_Customer|null
     */
    public function getCurrentCustomer()
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return Mage::getSingleton('adminhtml/session_quote')->getCustomer();
        }

        if($customer = Mage::getSingleton('customer/session')->isLoggedIn()) {
            return Mage::getSingleton('customer/session')->getCustomer();
        }

        if ($this->_isPersistent()) {
            return $this->_getPersistentHelper()->getCustomer();
        }

        return null;
    }

    /**
     * Retrieve persistent helper
     *
     * @return Mage_Persistent_Helper_Session
     */
    protected function _getPersistentHelper()
    {
        return Mage::helper('persistent/session');
    }


    /**
     * @return bool
     */
    protected function _isPersistent()
    {
        if(! Mage::helper('core')->isModuleEnabled('Mage_Persistent')
            || Mage::getSingleton('customer/session')->isLoggedIn()) {
            return false;
        }

        if ($this->_getPersistentHelper()->isPersistent()) {
            return true;
        }

        return false;
    }
}