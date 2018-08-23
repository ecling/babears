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
 * @category  Adyen
 * @package Adyen_Payment
 * @copyright Copyright (c) 2016 AAOO Tech Ltd. (http://www.aaoo-tech.com)
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     AAOO
 * @property   AAOO Tech Ltd.
 * @copyright  Copyright (c) 2016 AAOO Tech Ltd. (http://www.aaoo-tech.com)
 */

class Adyen_Payment_Model_Source_POS_IpFilter {
    public function toOptionArray() {
        $_options = array(
            array( 'value' => '0', 'label' => 'Disabled' ),
            array( 'value' => '1', 'label' => 'Specific IPs' ),
            array( 'value' => '2', 'label' => 'IP Range' ),
        );
        return $_options;
    }

    public function toOptionHash() {
        return array(
            '0' => 'Disabled',
            '1' => 'Specific IPs', 
            '2' => 'IP Range',
        );
    }
}
