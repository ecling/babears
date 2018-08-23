<?php
class Tm_GoogleMap_Model_Source_Types_Maptypes
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'ROADMAP', 'label'=>Mage::helper('adminhtml')->__('Roadmap')),
            array('value' => 'SATELLITE', 'label'=>Mage::helper('adminhtml')->__('Satellite ')),
            array('value' => 'HYBRID', 'label'=>Mage::helper('adminhtml')->__('Hybrid')),
            array('value' => 'TERRAIN', 'label'=>Mage::helper('adminhtml')->__('Terrain')),
        );
    }
}
?>