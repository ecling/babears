<?php

$this->startSetup();

$this->getConnection()->addColumn(
    $this->getTable('chargepayment/customercard'),
    'save_card',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment'   => 'Save card',
    )
);


$this->endSetup();