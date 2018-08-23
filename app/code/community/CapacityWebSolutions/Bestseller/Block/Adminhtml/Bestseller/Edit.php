<?php
/***************************************************************************
 Extension Name	: Bestseller Products
 Extension URL	: http://www.magebees.com/magento-bestseller-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_Bestseller_Block_Adminhtml_Bestseller_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct()
	{
		parent::__construct();
		$this->_objectId = 'id';
		$this->_blockGroup = 'bestseller';
		$this->_controller = 'adminhtml_bestseller';
		$this->_updateButton('save', 'label','Save');
		$this->_updateButton('delete','label','Delete');
		$this->_addButton('save_and_continue', array(
             'label' => Mage::helper('bestseller')->__('Save And Continue Edit'),
             'onclick' => 'saveAndContinueEdit()',
             'class' => 'save' 
         ), -100);
		
         $this->_formScripts[] = "
            function saveAndContinueEdit(){
				editForm.submit($('edit_form').action + 'back/edit/');
            }
			 
			 ";
		$this->setId('bestseller_edit');
	}
	
	public function getHeaderText()
    {
        if( Mage::registry('bestseller_data') && Mage::registry('bestseller_data')->getBestsellerId() ) {
            return Mage::helper('bestseller')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('bestseller_data')->getSku()));
        } else {
            return Mage::helper('bestseller')->__('Select Products');
        }
    }	
}