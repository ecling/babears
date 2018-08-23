<?php

Class Martin_Flytcloud_Block_Adminhtml_Shipping_Type_Country_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_blockGroup='flytcloud';
        $this->_objectId = 'entity_id';
        $this->_controller = 'shipping_type_country';

        parent::__construct();

        $this->_updateButton('save', 'label', Mage::helper('adminhtml')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('adminhtml')->__('Delete Item'));
    }

    public function getHeaderText()
    {
        if (Mage::registry('shipping_type_country')->getId()) {
            return Mage::helper('adminhtml')->__("Edit Item '%s'", $this->escapeHtml(Mage::registry('shipping_type_country')->getId()));
        }
        else {
            return Mage::helper('adminhtml')->__('New Item');
        }
    }  
}