<?php
abstract class Mage_Adminhtml_Block_Sales_Order_Edit_Abstract extends Mage_Adminhtml_Block_Widget{
    public function getOrder(){
        if(is_null($this->_order)){
            $this->_order = Mage::registry('current_order');
        }
        return $this->_order;
    }
    public function getStoreId()
    {
        return $this->getOrder()->getStoreId();
    }
    public function getStore(){
        $id = $this->getStoreId();
        return Mage::app()->getStore($id);
    }
    public function formatPrice($value)
    {
        return $this->getStore()->formatPrice($value);
    }

    public function convertPrice($value, $format=true)
    {
        return $this->getStore()->convertPrice($value, $format);
    }
}