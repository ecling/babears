<?php
class Magestore_Fblogin_Model_Config_Select
{
    public function toOptionArray()
    {
        return array(
            array('value' => '0', 'label'=>Mage::helper('adminhtml')->__('Account Page')),
            array('value' => '1', 'label'=>Mage::helper('adminhtml')->__('Cart Page')),
			array('value' => '2', 'label'=>Mage::helper('adminhtml')->__('Home Page')),
			array('value' => '3', 'label'=>Mage::helper('adminhtml')->__('Current Page')),
			array('value' => '4', 'label'=>Mage::helper('adminhtml')->__('Custom Page')),
		);
    }
}