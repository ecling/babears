<?php
/***************************************************************************
 Extension Name	: New Products
 Extension URL	: http://www.magebees.com/magento-new-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_Newproducts_Block_Adminhtml_Newproducts_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct()
	{
		parent::__construct();
		$this->_objectId = 'id';
		$this->_blockGroup = 'newproducts';
		$this->_controller = 'adminhtml_newproducts';
		$this->_updateButton('save', 'label','Save');
		$this->_updateButton('delete','label','Delete');
		$this->_addButton('save_and_continue', array(
             'label' => Mage::helper('newproducts')->__('Save And Continue Edit'),
             'onclick' => 'saveAndContinueEdit()',
             'class' => 'save' 
         ), -100);
		
         $this->_formScripts[] = "
            function saveAndContinueEdit(){
				editForm.submit($('edit_form').action + 'back/edit/');
            }
			 
			 ";
		$this->setId('newproducts_edit');
	}
	
	public function getHeaderText()
    {
        if( Mage::registry('newproducts_data') && Mage::registry('newproducts_data')->getNewproductsId() ) {
            return Mage::helper('newproducts')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('newproducts_data')->getSku()));
        } else {
            return Mage::helper('newproducts')->__('Select Products');
        }
    }	
}