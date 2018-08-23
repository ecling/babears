<?php

class Tm_Facebook_Model_Source_Colorscheme_Values
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'light', 'label'=>Mage::helper('adminhtml')->__('Light')),
            array('value' => 'dark', 'label'=>Mage::helper('adminhtml')->__('Dark ')),
        );
    }
}