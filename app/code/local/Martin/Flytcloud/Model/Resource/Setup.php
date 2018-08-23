<?php

class Martin_Flytcloud_Model_Resource_Setup extends Mage_Core_Model_Resource_Setup
{
    
    
    public function createShippinStatusTable()
    {
        $table=$this->getTable('flytcloud/shipping_status');
        $this->run("   create table if not exists $table(
                 `entity_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
                 `shipping_status` varchar(30) NOT NULL COMMENT 'Shipping Status' ,   
                 UNIQUE KEY `UNQ_SHIPPING_STATUS_ID` (`shipping_status`),
                 PRIMARY KEY (`entity_id`)
               ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ");
    }
    public function createOrderShippingStatusTable(){
        $table=$this->getTable('flytcloud/order_shipping_status');
        $this->run("create table if not exists $table(
                `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
                `order` int(10) NOT NULL  COMMENT 'Order ID',
                `shipping_status` int(11) NOT NULL COMMENT 'Shipping Status ID' ,
                `flytcloud_order_id` varchar(50) NOT NULL COMMENT 'Flytcloud Order ID' ,
                `update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP  on update CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `UNQ_SALES_ORD_ID_SHIPPING_STATUS_ID` (`order`,`shipping_status`),
                CONSTRAINT `FK_SALES_ORD_ID_SALES_FLAT_ORD_ID` FOREIGN KEY (`order`) REFERENCES `sales_flat_order` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `FK_SP_STATUS_ID_FLYCLOUD_SP_STATUS_ID` FOREIGN KEY (`shipping_status`) REFERENCES `flytcloud_shipping_status` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ");
    }
    
    
    
    public function crtBackendTableForOrderAttr($attrCode)
    {
          $helper=Mage::helper('flytcloud/order_attribute');
          $table=$helper->getAttrTable("shipping_status");

          if($table){
                $sql="CREATE TABLE IF NOT EXISTS `$table` (
                        `value_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Value ID',
                        `entity_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Entity Type ID',
                        `attribute_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Attribute ID',
                        `store_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Store ID',
                        `entity_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Entity ID',
                        `value` varchar(255) DEFAULT NULL COMMENT 'Value',
                        PRIMARY KEY (`value_id`),
                        UNIQUE KEY `UNQ_SALES_ORD_ENTT_VCHR_ENTT_ID_ATTR_ID_STORE_ID` (`entity_id`,`attribute_id`,`store_id`),
                        KEY `IDX_SALES_ORD_ENTITY_VARCHAR_ATTRIBUTE_ID` (`attribute_id`),
                        KEY `IDX_SALES_ORD_ENTITY_VARCHAR_STORE_ID` (`store_id`),
                        KEY `IDX_SALES_ORD_ENTITY_VARCHAR_ENTITY_ID` (`entity_id`),
                        CONSTRAINT `FK_SALES_ORD_ENTITY_VARCHAR_STORE_ID_CORE_STORE_STORE_ID` FOREIGN KEY (`store_id`) REFERENCES `core_store` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                        CONSTRAINT `FK_SALES_ORD_ENTT_VCHR_ATTR_ID_EAV_ATTR_ATTR_ID` FOREIGN KEY (`attribute_id`) REFERENCES `eav_attribute` (`attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                        CONSTRAINT `FK_SALES_ORD_ENTT_VCHR_ENTT_ID_CAT_PRD_ENTT_ENTT_ID` FOREIGN KEY (`entity_id`) REFERENCES `catalog_product_entity` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
                       ) ENGINE=InnoDB AUTO_INCREMENT=40721 DEFAULT CHARSET=utf8 COMMENT='Sales order Varchar Attribute Backend Table.create by martin/flytcloud module'
                       ; ";

               $this->run($sql);
          }else{
              throw new Exception("create table with non-tablename");
          }
    }
    
    public function addAttribute(Varien_Object $attrInfo,$forceUpdate=false)
    {

        try{
            $attrCode=$attrInfo->getAttributeCode();

            $orderEntityType=Mage::getModel('eav/entity_type')->load('order','entity_type_code');
            $orderTypeId=$orderEntityType->getId();
            $shippingStsAttr=Mage::getModel($orderEntityType->getAttributeModel());
            $shippingStsAttr=$shippingStsAttr->loadByCode($orderTypeId,$attrCode);

            if(!$shippingStsAttr->getId() || ($shippingStsAttr->getId() && $forceUpdate))
            {
                $shippingStsAttr->setData($attrInfo->getData());
                $shippingStsAttr->save();
            }
            return true;
        }catch(Exception $e){
            Mage::logException($e);
            return false;
        }
    }
    
    public function addAttrToGrid($attrCode)
    {
          $helper=Mage::helper('flytcloud/order_attribute');
          $table=Mage::getResourceModel('sales/order')->getGridTable();
          $resource=Mage::getSingleton('core/resource');
          $dsc=$resource->getConnection('read')->describeTable($table);
          
          $sql="describe $table $attrCode";
          $readadapter=Mage::getSingleton('core/resource')->getConnection('write');
          $result=$readadapter->fetchAll($sql);

          if(count($result)>0)
          {
              throw new Exception("$attrCode already exsist in table $table");  
          }else{
            if($table){
                $updateSQL="alter table $table add  $attrCode varchar(30)"    ;
                $adapter=Mage::getSingleton('core/resource')->getConnection('write');
                $adapter->query($updateSQL);
            }
          }

    }

}
