<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$installer=$this;

$installer->startSetup();

$table=$installer->getTable('flytcloud/shipping_type');
$installer->run("   create table if not exists $table(
         `entity_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
         `shipping_type` varchar(30) NOT NULL COMMENT 'Shipping Type' ,
         `shipping_type_code` varchar(30) NOT NULL COMMENT 'Shipping Type Code' ,
         UNIQUE KEY `UNQ_SHIPPING_TYPE` (`shipping_type`),
         PRIMARY KEY (`entity_id`)
       ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ");


//$installer->run("insert ignore into $table (shipping_type) values ('WISH邮') , "
//        . "('飞特全球邮递平邮'),('香港德国专线平邮'),('香港德国专线挂号'),('香港小包平邮'),('飞特荷兰专线')"
//        . ",('欧邮宝'),('欧邮宝挂号'),('老挝小包'),('中国邮政小包挂号'),('中国邮政小包平邮') ;");



$installer->run("insert ignore into $table (shipping_type,shipping_type_code) values ('飞特全球邮递平邮','FGMSN') , "
." ('香港德国专线平邮','DEAM'),('香港德国专线挂号','DEAM-R'),('香港小包平邮','HKAM'),"
        ." ('香港小包挂号','HKRAM'),('飞特荷兰专线','NLFC'),('欧邮宝','EU-PACKET'),"
        ." ('欧邮宝挂号','EU-PACKET-R') ,('广州线下EUB','GZ-OfflineEPACKET') ;");


$table=$installer->getTable('flytcloud/shipping_type_country');

$installer->run("   create table if not exists $table(
         `entity_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
         `shipping_type_id` int(11) NOT NULL COMMENT 'Shipping Type ID' ,
         `country_id` varchar(2) NOT NULL COMMENT 'Country ID' ,
         UNIQUE KEY `UNQ_SHIPPING_TYPE` (`shipping_type_id`,`country_id`),
         PRIMARY KEY (`entity_id`),
         CONSTRAINT `FK_STC_COUNTRY_ID_DC_COUNTRY_ID` FOREIGN KEY (`country_id`) REFERENCES `directory_country` (`country_id`) ON DELETE CASCADE ON UPDATE CASCADE
       ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ");




$installer->endSetup();
