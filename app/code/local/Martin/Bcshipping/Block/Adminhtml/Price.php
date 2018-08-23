<?php

class Martin_Bcshipping_Block_Adminhtml_Price extends Mage_Adminhtml_Block_Widget_Grid_Container{
    public function __construct()
    {   $this->_blockGroup="bcshipping";
        $this->_controller = 'adminhtml_price';
        $this->_headerText = Mage::helper('adminhtml')->__('Shipping Methed Rule');
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add New Rule');
        parent::__construct();
    }
    protected function _toHtml()
    {
        Mage::dispatchEvent('shipping_type_html_before', array('block' => $this));
        return parent::_toHtml();
    }
}