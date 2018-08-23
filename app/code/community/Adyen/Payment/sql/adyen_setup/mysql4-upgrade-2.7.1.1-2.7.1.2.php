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

/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer = new Mage_Sales_Model_Resource_Setup('core_setup');

$entities = array(
    'quote_item',
    'quote_address_item',
    'order_item'
);

foreach ($entities as $entity) {
    $installer->addAttribute($entity, 'adyen_pre_order', array(
        'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
        'visible'  => false,
        'required' => false
    ));
}

$installer->endSetup();
