<?php
/***************************************************************************
 Extension Name	: Bestseller Products
 Extension URL	: http://www.magebees.com/magento-bestseller-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_Bestseller_Block_Adminhtml_Bestseller extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_bestseller';
		$this->_blockGroup = 'bestseller';
		$this->_headerText = Mage::helper('bestseller')->__('Manage Products Manually');
		$this->_addButtonLabel = Mage::helper('bestseller')->__('Select Products');
		parent::__construct();
	}
}