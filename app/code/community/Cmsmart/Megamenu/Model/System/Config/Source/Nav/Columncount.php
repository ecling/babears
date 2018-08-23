<?php
/*
* Name Extension: Megamenu
*/
class Cmsmart_Megamenu_Model_System_Config_Source_Nav_ColumnCount
{
    public function toOptionArray()
    {
        return array(
            array('value' => 2, 'label' => Mage::helper('megamenu')->__('2 Columns')),
            array('value' => 3, 'label' => Mage::helper('megamenu')->__('3 Columns')),
			array('value' => 4, 'label' => Mage::helper('megamenu')->__('4 Columns'))            
        );
    }
}