<?php

class Martin_Flytcloud_Model_Source_Shipping_Status extends Mage_Eav_Model_Entity_Attribute_Source_Table
{
    public function getAllOptions()
    {
        if (!$this->_options) {
            $collection=Mage::getModel('flytcloud/shipping_status')->getCollection();
            $optionArr=array();
            foreach($collection as $item)
            {
                $optionArr[$item->getId()]=$item->getData('shipping_status');
            }
            $this->_options=$optionArr;
        }
        return $this->_options;
    }
}