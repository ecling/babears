<?php 
class Tm_Productlistgallery_Model_Source_Types_Hovertype
{
    public function toOptionArray()
    {
        return array(
			array('value' => 'disable_hover', 'label'=>Mage::helper('adminhtml')->__('Disable')),
            array('value' => 'thumbnails', 'label'=>Mage::helper('adminhtml')->__('Thumbnail')),
			array('value' => 'carusel_images', 'label'=>Mage::helper('adminhtml')->__('Carusel Images'))
        );
    }
}
?>