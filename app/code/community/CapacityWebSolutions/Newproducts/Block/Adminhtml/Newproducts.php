<?php
/***************************************************************************
 Extension Name	: New Products
 Extension URL	: http://www.magebees.com/magento-new-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_Newproducts_Block_Adminhtml_Newproducts extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_newproducts';
		$this->_blockGroup = 'newproducts';
		$this->_headerText = Mage::helper('newproducts')->__('Manage New Products');
		$this->_addButtonLabel = Mage::helper('newproducts')->__('Select Products');
		parent::__construct();
	}
	
	protected function _prepareLayout() {
        $this->setChild('store_switcher', $this->getLayout()->createBlock('adminhtml/store_switcher', 'store_switcher')->setUseConfirm(false)
        );
        return parent::_prepareLayout();
    }

    public function getGridHtml() {

        return $this->getChildHtml('store_switcher') . $this->getChildHtml('grid');
    }
}