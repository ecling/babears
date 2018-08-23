<?php

class Tm_Twitter_Model_Source_ColorScheme_Values
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'light', 'label'=>Mage::helper('adminhtml')->__('Light')),
            array('value' => 'dark', 'label'=>Mage::helper('adminhtml')->__('Dark ')),
        );
    }
}