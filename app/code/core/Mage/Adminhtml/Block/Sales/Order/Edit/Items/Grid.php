<?php
class Mage_Adminhtml_Block_Sales_Order_Edit_Items_Grid extends Mage_Adminhtml_Block_Sales_Order_Edit_Abstract{
    public function getItems(){
        $items = $this->getParentBlock()->getItems();
        return $items;
    }
    
    public function usedCustomPriceForItem($item)
    {
        return $item->hasCustomPrice();
    }
    
    public function canApplyCustomPrice($item)
    {
        return !$item->isChildrenCalculated();
    }
    
    public function getItemExtraInfo($item)
    {
        return $this->getLayout()
            ->getBlock('order_item_extra_info')
            ->setItem($item);
    }
    public function isGiftMessagesAvailable($item = null)
    {
        return false;
    }
    
    public function getConfigureButtonHtml($item)
    {
        $product = $item->getProduct();

        $options = array('label' => Mage::helper('sales')->__('Configure'));
        if ($product->canConfigure()) {
            $options['onclick'] = sprintf('order.showQuoteItemConfiguration(%s)', $item->getId());
        } else {
            $options['class'] = ' disabled';
            $options['title'] = Mage::helper('sales')->__('This product does not have any configurable options');
        }

        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData($options)
            ->toHtml();
    }
}