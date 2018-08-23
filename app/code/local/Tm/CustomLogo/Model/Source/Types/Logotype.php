<?php 
class Tm_CustomLogo_Model_Source_Types_Logotype
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'logo_images', 'label'=>Mage::helper('adminhtml')->__('Logo Images')),
            array('value' => 'logo_text', 'label'=>Mage::helper('adminhtml')->__('Logo Text'))
        );
    }
}
?>