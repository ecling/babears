<?php
/***************************************************************************
 Extension Name	: Bestseller Products
 Extension URL	: http://www.magebees.com/magento-bestseller-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/ 
class CapacityWebSolutions_Bestseller_Block_Adminhtml_Bestseller_Edit_Tab_Products extends Mage_Adminhtml_Block_Widget_Form
{
	public function __construct() {
		parent::__construct();
		$this->setTemplate('bestseller/product.phtml');
	}

	protected function getProductIds() {
		$data = Mage::registry('bestseller_data');
		$prd_model = Mage::getModel('bestseller/bestseller')->getCollection();
		
		$_productList = array();
		
		foreach($prd_model as $prd_data){
			$_productList[] = $prd_data->getData('sku');
		} 
	
		return is_array($_productList) ? $_productList : array();
	}

	public function getIdsString() {
		return implode(', ', $this->getProductIds());
	}
	
}