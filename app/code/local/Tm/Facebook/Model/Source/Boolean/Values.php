<?php

class Tm_Facebook_Model_Source_Boolean_Values
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'true', 'label'=>Mage::helper('adminhtml')->__('True')),
            array('value' => 'false', 'label'=>Mage::helper('adminhtml')->__('False ')),
        );
    }
}