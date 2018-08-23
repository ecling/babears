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


/*
 * clear the field payment/adyen_hpp/allowspecific because this is not a setting anymore
 */

$notificationPath = "payment/adyen_hpp/allowspecific";
updateConfigValueNotificationPath($notificationPath);


// needs to be unique function name otherwise clean install will fail
function updateConfigValueNotificationPath($path) {
    try {
        $collection = Mage::getModel('core/config_data')->getCollection()
            ->addFieldToFilter('path', array('like' => $path ));

        if ($collection->count() > 0) {
            foreach ($collection as $coreConfig) {
                $coreConfig->setValue('0')->save();
            }
        }
    } catch (Exception $e) {
        Mage::log($e->getMessage(), Zend_Log::ERR);
    }
}

