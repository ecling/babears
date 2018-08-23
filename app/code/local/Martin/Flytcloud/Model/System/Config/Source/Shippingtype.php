<?php
class Martin_Flytcloud_Model_System_Config_Source_Shippingtype extends Varien_Object{
    public function toOptionArray(){
        $collection=Mage::getModel('flytcloud/shipping_type')->getCollection();
        //$optionArr=$collection->toOptionArray();
        $option = array();
        foreach($collection as $type){
            $option[] = array(
                'value'=> $type->getShippingTypeCode(),
                'label'=> $type->getShippingType()
            );
        }
        return $option;
    }
}