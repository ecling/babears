<?php
class Mage_Adminhtml_Block_Sales_Order_Edit_Items extends Mage_Adminhtml_Block_Sales_Order_Edit_Abstract{
    protected $_order = null;
    
    public function getItems()
    {
        return $this->getOrder()->getAllVisibleItems();
    }
}