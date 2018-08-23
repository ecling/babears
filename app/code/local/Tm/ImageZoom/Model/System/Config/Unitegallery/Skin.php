<?php

class Tm_ImageZoom_Model_System_Config_Unitegallery_Skin
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'default', 'label' =>Mage::helper('adminhtml')->__('Default')),
            array('value' => 'alexis', 'label'  =>Mage::helper('adminhtml')->__('Alexis')),
        );
    }
}