<?php
/*
* Name Extension: Megamenu
*/
class Cmsmart_Megamenu_Block_Megamenu extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
        return parent::_prepareLayout();
        
    }
    
     public function getAdmintestimonials()     
     { 
        if (!$this->hasData('megamenu')) {
            $this->setData('megamenu', Mage::registry('megamenu'));
        }
        return $this->getData('megamenu');
        
    }
}