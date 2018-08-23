<?php

class Tm_ImageZoom_Model_System_Config_Custombox_Fills
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'white', 'label' =>Mage::helper('adminhtml')->__('White')),
            array('value' => 'black', 'label'  =>Mage::helper('adminhtml')->__('Black')),
        );
    }
}