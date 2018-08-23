<?php
class Martin_Flytcloud_Block_Adminhtml_Shipping_Type_Country_Edit_Tabs extends 
Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('page_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('adminhtml')->__('Item Info'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('main_section', array(
            'label'     => Mage::helper('adminhtml')->__('Item Info'),
            'title'     => Mage::helper('adminhtml')->__('Item Info'),
            'content'   => $this->getLayout()->createBlock('flytcloud/adminhtml_shipping_type_country_edit_tab_main')->toHtml(),
            'active'    => true
        ));

        return parent::_beforeToHtml();
    }   
}
