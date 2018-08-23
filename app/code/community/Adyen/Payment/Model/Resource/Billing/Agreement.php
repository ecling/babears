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
 * @package	    Adyen_Payment
 * @copyright	Copyright (c) 2011 Adyen (http://www.adyen.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2015 Adyen BV (http://www.adyen.com)
 */

class Adyen_Payment_Model_Resource_Billing_Agreement
    extends Mage_Sales_Model_Resource_Billing_Agreement {

    /**
     * Add order relation to billing agreement
     *
     * @param int $agreementId
     * @param int $orderId
     * @return Mage_Sales_Model_Resource_Billing_Agreement
     */
    public function addOrderRelation($agreementId, $orderId)
    {
        /*
         * needed for subscription module, only available in version >= 1.8
         */
        if(method_exists($this->_getWriteAdapter(), 'insertIgnore')) {
            $this->_getWriteAdapter()->insertIgnore(
                $this->getTable('sales/billing_agreement_order'), array(
                    'agreement_id'  => $agreementId,
                    'order_id'      => $orderId
                )
            );
        } else {
            // use the default insert for <= 1.7 version
            try {
                parent::addOrderRelation($agreementId, $orderId);
            } catch(Exception $e) {
                // do not log this because this is a Integrity constraint violation solved in 1.8 by insertIgnore
            }
        }
        return $this;
    }
}