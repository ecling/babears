<?php

class Martin_Recommend_Block_Adminhtml_Recommend extends Mage_Adminhtml_Block_Widget_Grid_Container{
    public function __construct()
    {   $this->_blockGroup="recommend";
        $this->_controller = 'adminhtml_recommend';
        $this->_headerText = Mage::helper('adminhtml')->__('Recommend Product');
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add New');
        parent::__construct();
    }
    protected function _toHtml()
    {
        return parent::_toHtml();
    }
}