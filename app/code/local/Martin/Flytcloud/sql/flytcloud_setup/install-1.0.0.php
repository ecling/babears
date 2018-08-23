<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$installer=$this;

$installer->startSetup();

$installer->createShippinStatusTable();

$installer->createOrderShippingStatusTable();

$shippingStatusTable=$this->getTable('flytcloud/shipping_status');
$installer->run("insert ignore into $shippingStatusTable (shipping_status) values ('Submitted to Ship')");
$installer->run("insert ignore into $shippingStatusTable (shipping_status) values ('completed')");


//$arrInfo=new Varien_Object();
//
//$attrCode="shipping_status";
//$backendType="varchar";
//$frontend_input='select';
//$frontendLable="Shipping Status";
//$sourceModel='flytlcoud/source_shipping_status';
//$arrInfo->setData('entity_type_id',$orderTypeId)
//                ->setData('attribute_code',$attrCode)
//                ->setData('backend_type',$backendType)
//                ->setData('frontend_input',$frontend_input)
//                ->setData('frontend_lable',$frontendLable)
//                ->setData('source_model',$sourceModel);
//            
//$installer->addAttribute($arrInfo);
//
//$installer->crtBackendTableForOrderAttr($attrCode);
//
//$installer->addAttrToGrid($attrCode);

$installer->endSetup();
