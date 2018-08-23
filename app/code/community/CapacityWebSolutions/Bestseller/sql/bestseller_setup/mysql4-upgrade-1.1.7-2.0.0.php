<?php

$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS `".$this->getTable('bestseller')."` (
  `bestseller_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(32) NOT NULL DEFAULT '',
  `product_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`bestseller_id`),
  KEY `IDX_BESTSELLER_PRODUCT_SKU` (`sku`),
  KEY `IDX_BESTSELLER_PRODUCT_ID` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `".$this->getTable('bestseller')."`
  ADD CONSTRAINT `IDX_BESTSELLER_PRODUCT_SKU` FOREIGN KEY (`sku`) REFERENCES `".$this->getTable('catalog_product_entity')."` (`sku`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `IDX_BESTSELLER_PRODUCT_ID` FOREIGN KEY (`product_id`) REFERENCES `".$this->getTable('catalog_product_entity')."` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE;	
 
");

$installer->endSetup(); 