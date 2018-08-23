<?php
/*
* Name Extension: Megamenu
*/
class Cmsmart_Megamenu_Block_Catalog_Category_Tabs_Form extends Mage_Adminhtml_Block_Widget_Form
{
	public function getCategory()
    {
        return Mage::registry('current_category');
    }
	protected function _prepareForm()
	{
		$form = new Varien_Data_Form();
        $this->setForm($form);
	    
     	$fieldset = $form->addFieldset('megamenu_form', array('legend'=>Mage::helper('megamenu')->__('Menu top')));
		
			$this->setTemplate('cmsmart/megamenu/menutop.phtml');
		
		return parent::_prepareForm();
	}
	
	
}
