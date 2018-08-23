<?php

class Martin_Flytcloud_Helper_Order_Attribute extends Mage_Core_Helper_Abstract
{
    public function getAttrTable($attrCode)
    {
        try{
            $orderEntityType=Mage::getModel('eav/entity_type')->load('order','entity_type_code');

            $shippingStsAttr=Mage::getModel($orderEntityType->getAttributeModel());
            $shippingStsAttr->load($attrCode,'attribute_code');
            $shippingStsAttr->setEntityTypeId($orderEntityType->getEntityTypeId());
            

            $backend=$shippingStsAttr->getBackend();

            $table=$backend->getTable();
            
            return $table;
        }catch(Exception $e){
            Mage::logException($e);
            return null;
        }
    }
}