<?php

class Tm_ImageZoom_Model_System_Config_Custombox_Effects
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'fadein', 'label'			=>Mage::helper('adminhtml')->__('FadeIn')),
            array('value' => 'slide', 'label'			=>Mage::helper('adminhtml')->__('Slide')),
            array('value' => 'newspaper', 'label'	 	=>Mage::helper('adminhtml')->__('Newspaper')),
            array('value' => 'fall', 'label'			=>Mage::helper('adminhtml')->__('Fall')),
            array('value' => 'sidefall', 'label'		=>Mage::helper('adminhtml')->__('Sidefall')),
            array('value' => 'blur', 'label'			=>Mage::helper('adminhtml')->__('Blur')),
            array('value' => 'flip', 'label'			=>Mage::helper('adminhtml')->__('Flip')),
            array('value' => 'sign', 'label'			=>Mage::helper('adminhtml')->__('Sign')),
            array('value' => 'superscaled', 'label'		=>Mage::helper('adminhtml')->__('Superscaled')),
            array('value' => 'slit', 'label'			=>Mage::helper('adminhtml')->__('Slit')),
            array('value' => 'rotate', 'label'			=>Mage::helper('adminhtml')->__('Rotate')),
            array('value' => 'letmein', 'label'			=>Mage::helper('adminhtml')->__('Letmein')),
            array('value' => 'makeway', 'label'			=>Mage::helper('adminhtml')->__('Makeway')),
            array('value' => 'slip', 'label'			=>Mage::helper('adminhtml')->__('Slip')),
            array('value' => 'corner', 'label'			=>Mage::helper('adminhtml')->__('Corner')),
            array('value' => 'slidetogether', 'label'	=>Mage::helper('adminhtml')->__('Slidetogether')),
            array('value' => 'scale', 'label'			=>Mage::helper('adminhtml')->__('Scale')),
            array('value' => 'door', 'label'			=>Mage::helper('adminhtml')->__('Door')),
            array('value' => 'push', 'label'			=>Mage::helper('adminhtml')->__('Push')),
            array('value' => 'contentscale', 'label'	=>Mage::helper('adminhtml')->__('Contentscale')),
            array('value' => 'swell', 'label'			=>Mage::helper('adminhtml')->__('Swell')),
            array('value' => 'rotatedown', 'label'		=>Mage::helper('adminhtml')->__('Rotatedown')),
            array('value' => 'flash', 'label'			=>Mage::helper('adminhtml')->__('Flash')),
        );
    }
}