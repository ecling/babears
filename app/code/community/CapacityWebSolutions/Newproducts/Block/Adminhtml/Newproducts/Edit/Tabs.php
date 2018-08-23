<?php
/***************************************************************************
 Extension Name	: New Products
 Extension URL	: http://www.magebees.com/magento-new-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_Newproducts_Block_Adminhtml_Newproducts_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	public function __construct()
    {
        parent::__construct();
        $this->setId('newproducts_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle('Newproducts Information');
    }
    
	protected function _beforeToHtml()
    {
	  	$this->addTab('product_section', array(
            'label'     => Mage::helper('newproducts')->__('Products'),
            'title'     => Mage::helper('newproducts')->__('Products'),
            'content'   => $this->getLayout()->createBlock('newproducts/adminhtml_newproducts_edit_tab_products')->toHtml(),
        ));
			
		return parent::_beforeToHtml();
    }
}