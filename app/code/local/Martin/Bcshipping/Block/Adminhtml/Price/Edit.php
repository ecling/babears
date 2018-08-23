<?php

Class Martin_Bcshipping_Block_Adminhtml_Price_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_blockGroup='bcshipping';
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_price';

        parent::__construct();

        $this->_updateButton('save', 'label', Mage::helper('adminhtml')->__('Save Rule'));
        $this->_updateButton('delete', 'label', Mage::helper('adminhtml')->__('Delete Rule'));
    }

    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', array('_current'=>true));
    }

    public function getHeaderText()
    {
        if (Mage::registry('shipping_price')->getId()) {
            return Mage::helper('adminhtml')->__("Edit Item '%s'", $this->escapeHtml(Mage::registry('shipping_price')->getId()));
        }
        else {
            return Mage::helper('adminhtml')->__('New Rule');
        }
    }
}