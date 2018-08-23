<?php

class Tm_Twitter_Model_Source_Chrome_Layouts
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'noheader', 'label'=>Mage::helper('adminhtml')->__('Hide Header')),
            array('value' => 'nofooter', 'label'=>Mage::helper('adminhtml')->__('Hide Footer ')),
            array('value' => 'noborders', 'label'=>Mage::helper('adminhtml')->__('Hide Borders ')),
            array('value' => 'noscrollbar', 'label'=>Mage::helper('adminhtml')->__('Hide Scrollbars ')),
            array('value' => 'transparent', 'label'=>Mage::helper('adminhtml')->__('Hide Background ')),
        );
    }
}