<?php

class Martin_Flytcloud_Block_Shipping_Type_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('shippingTypeCountryGrid');
        $this->setDefaultSort('shpiping_type');
        $this->setDefaultDir('asc');
        $this->setUseAjax(true);
        $this->setTemplate('flytcloud/grid.phtml');
    }
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('entity_id');
        $this->getMassactionBlock()->setUseSelectAll(false);

        
            $this->getMassactionBlock()->addItem('delete', array(
                 'label'=> Mage::helper('sales')->__('delete'),
                 'url'  => $this->getUrl('*/shipping_type_country/delete'),
            ));
            
        return $this;
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
            'header'    => Mage::helper('adminhtml')->__('Shipping Type'),
            'index'     => 'shipping_type'
        ));

        $this->addColumn('country', array(
            'header'    => Mage::helper('adminhtml')->__('Country'),
            'index'     => 'country'
        ));

       

        return parent::_prepareColumns();
    }
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
