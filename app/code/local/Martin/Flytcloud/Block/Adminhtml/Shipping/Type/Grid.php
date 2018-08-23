<?php

class Martin_Flytcloud_Block_Adminhtml_Shipping_Type_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('shippingTypeCountryGrid');
        $this->setDefaultSort('shpiping_type');
        $this->setDefaultDir('asc');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('flytcloud/shipping_type_country_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('shipping_type', array(
            'header'    => Mage::helper('adminhtml')->__('User Name'),
            'index'     => 'shipping_type'
        ));

        $this->addColumn('country', array(
            'header'    => Mage::helper('adminhtml')->__('First Name'),
            'index'     => 'country'
        ));

       

        return parent::_prepareColumns();
    }
}
