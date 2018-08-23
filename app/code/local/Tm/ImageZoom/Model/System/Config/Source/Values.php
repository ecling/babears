<?php

class Tm_ImageZoom_Model_System_Config_Source_Values
{
    public function toOptionArray()
    {
        return array(
        	array('value' => 'default', 	 'label'=>Mage::helper('adminhtml')->__('Default')),
            array('value' => 'unitegallery', 'label'=>Mage::helper('adminhtml')->__('UniteGallery')),
        );
    }
}