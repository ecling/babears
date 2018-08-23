<?php

$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS `".$this->getTable('cws_newproducts')."` (
  `newproducts_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(64) NOT NULL,
  `store_id` text NOT NULL,
  PRIMARY KEY (`newproducts_id`),
  KEY `IDX_NEWPRODUCTS_PRODUCT_SKU` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `".$this->getTable('cws_newproducts')."`
  ADD CONSTRAINT `FK_NEWPRODUCTS_PRODUCT_SKU` FOREIGN KEY (`sku`) REFERENCES `".$this->getTable('catalog_product_entity')."` (`sku`) ON DELETE CASCADE ON UPDATE CASCADE;	
 ");

if(in_array($this->getTable('permission_block'),$installer->getConnection()->listTables())){
$installer->run("
	INSERT INTO {$this->getTable('permission_block')} (block_name,is_allowed) values ('newproducts/newproducts','1');
");
}

$installer->endSetup(); 