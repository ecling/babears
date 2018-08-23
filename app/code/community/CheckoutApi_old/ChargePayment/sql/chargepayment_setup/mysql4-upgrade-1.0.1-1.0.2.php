<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$this->startSetup();

$this->getConnection()->addColumn(
    $this->getTable('sales/order'),
    'charge_is_captured',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'default'   => 0,
        'comment'   => 'Is Captured by Checkout.com API',
    )
);

$this->getConnection()->addColumn(
    $this->getTable('sales/order'),
    'charge_is_voided',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'default'   => 0,
        'comment'   => 'Is Voided by Checkout.com API',
    )
);

$this->getConnection()->addColumn(
    $this->getTable('sales/order'),
    'charge_is_refunded',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'default'   => 0,
        'comment'   => 'Is Refunded by Checkout.com API',
    )
);

$this->endSetup();