<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$this->startSetup();

$this->getConnection()->addColumn(
    $this->getTable('sales/quote_payment'),
    'checkout_api_card_id',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'  => true,
        'default'   => null,
        'comment'   => 'Checkout API Card ID',
        'length'    => 100,
    )
);

$this->getConnection()->addColumn(
    $this->getTable('sales/order_payment'),
    'checkout_api_card_id',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'  => true,
        'default'   => null,
        'comment'   => 'Checkout API Card ID',
        'length'    => 100,
    )
);

$this->endSetup();