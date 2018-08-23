<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('chargepayment/customercard'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'ID')
    ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
    ), 'Customer ID from Magento')
    ->addColumn('card_id', Varien_Db_Ddl_Table::TYPE_TEXT, '100', array(
        'nullable'  => false,
    ), 'Card ID from Checkout API')
    ->addColumn('card_number', Varien_Db_Ddl_Table::TYPE_CHAR, '4', array(
        'nullable'  => false,
    ), 'Short Customer Credit Card Number')
    ->addColumn('card_type', Varien_Db_Ddl_Table::TYPE_TEXT, '20', array(
        'nullable'  => false,
    ), 'Credit Card Type')
    ->addColumn('save_card', Varien_Db_Ddl_Table::TYPE_TEXT, '1', array(
        'nullable'  => false,
    ), 'Save card');

$table->addIndex(
    $installer->getIdxName(
        $installer->getTable('chargepayment/customercard'),
        array(
            'customer_id',
            'card_id',
            'card_type',
            'save_card',
        ),
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    ),
    array(
        'customer_id',
        'card_id',
        'card_type',
        'save_card',
    ),
    array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE));

$installer->getConnection()->createTable($table);

$installer->endSetup();
