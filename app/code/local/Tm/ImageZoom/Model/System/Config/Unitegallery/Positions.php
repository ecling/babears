<?php

class Tm_ImageZoom_Model_System_Config_Unitegallery_Positions
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'left', 'label' =>Mage::helper('adminhtml')->__('Left')),
            array('value' => 'right', 'label'  =>Mage::helper('adminhtml')->__('Right')),
        );
    }
}