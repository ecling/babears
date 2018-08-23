<?php

class Martin_Bcshipping_Block_Adminhtml_Price_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('bcshippingPrice');
        $this->setDefaultSort('country');
        $this->setDefaultDir('asc');
        $this->setUseAjax(true);
    }
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('id');
        $this->getMassactionBlock()->setUseSelectAll(false);


        $this->getMassactionBlock()->addItem('delete', array(
            'label'=> Mage::helper('sales')->__('delete'),
            'url'  => $this->getUrl('*/*/delete'),
        ));

        return $this;
    }
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('bcshipping/price_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('id', array(
            'header'    => Mage::helper('adminhtml')->__('Rule Id'),
            'index'     => 'id'
        ));

        $this->addColumn('country', array(
            'header'    => Mage::helper('adminhtml')->__('Country'),
            'type'      => 'country',
            'index'     => 'country'
        ));

        $this->addColumn('condition_num', array(
            'header'    => Mage::helper('adminhtml')->__('Condition'),
            'index'     => 'condition_num'
        ));


        $this->addColumn('price', array(
            'header'    => Mage::helper('adminhtml')->__('Price'),
            'index'     => 'price'
        ));

        $this->addColumn('additional_price', array(
            'header'    => Mage::helper('adminhtml')->__('Additional Price'),
            'index'     => 'additional_price'
        ));

        return parent::_prepareColumns();
    }
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
