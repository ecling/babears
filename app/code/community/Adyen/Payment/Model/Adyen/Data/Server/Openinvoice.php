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
class Adyen_Payment_Model_Adyen_Data_Server_Openinvoice {

    public function retrieveDetail($request) {
        //TEST USING THE ADYEN TEST GUI:
//        if ($request->request->reference == 'testMerchantRef1')
//            $request->request->reference = '100000065';

        /**
         * authenticate data before return invoice lines 
         */
        $status = Mage::getModel('adyen/authenticate')
                ->authenticate(null, new Varien_Object(array('merchantAccountCode' => $request->request->merchantAccount)));
        if (!$status) {            
            return false;
        }

        Mage::log($request, Zend_Log::INFO, 'openinvoice-request.log', true);
            
        return Mage::getModel('adyen/adyen_data_openInvoiceDetailResult')->create($request);
    }

}