<?php

class Martin_Flytcloud_Block_Shipping_Type extends Mage_Adminhtml_Block_Widget_Grid_Container{
    public function __construct()
    {   $this->_blockGroup="flytcloud";
        $this->_controller = 'shipping_type';
        $this->_headerText = Mage::helper('adminhtml')->__('Items of shipping type-country ');
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add New Item');
        parent::__construct();
    }
    protected function _toHtml()
    {
        Mage::dispatchEvent('shipping_type_html_before', array('block' => $this));
        return parent::_toHtml();
    }
}