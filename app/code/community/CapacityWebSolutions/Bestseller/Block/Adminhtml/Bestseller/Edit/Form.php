<?php
/***************************************************************************
 Extension Name	: Bestseller Products
 Extension URL	: http://www.magebees.com/magento-bestseller-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_Bestseller_Block_Adminhtml_Bestseller_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm()
	{
		$form = new Varien_Data_Form(
				array(
					'id'=>'edit_form',
					'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
					'method'=>'post',
					'enctype' => 'multipart/form-data'
					)
				);
		$form->setUseContainer(true);
		$this->setForm($form);
		return parent::_prepareForm();
	}
}
