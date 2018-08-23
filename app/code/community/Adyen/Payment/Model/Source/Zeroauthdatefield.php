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
class Adyen_Payment_Model_Source_Zeroauthdatefield {

    /**
     * @return array
     */
    public function toOptionArray()
    {
        /* @var $resource Mage_Core_Model_Resource */
        $resource = Mage::getSingleton('core/resource');
        /* @var $resource Varien_Db_Adapter_Interface */
        $readConnection = $resource->getConnection('core_read');

        $dbname = (string)Mage::getConfig()->getNode('global/resources/default_setup/connection/dbname');

        $results = $readConnection->fetchAll("
SELECT
  `column_name`
FROM
  `information_schema`.`columns`
WHERE
  `table_schema` = '{$dbname}'
   AND `table_name` = '{$resource->getTableName('sales/order')}'
   AND `data_type` IN ('date','datetime','timestamp')
ORDER BY
  `table_name`, `ordinal_position`
        ");

        $rows = array();
        foreach ($results as $row) {
            $rows[] = array('value' => $row['column_name'], 'label' => $row['column_name']);
        }

        return $rows;
    }

}