<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/3 0003
 * Time: 11:46
 */

class Martin_Recommend_Block_Adminhtml_Recommend_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
        $collection = Mage::getResourceModel('recommend/recommend_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('recommend_id', array(
            'header'    => Mage::helper('adminhtml')->__('Id'),
            'index'     => 'recommend_id'
        ));

        $this->addColumn('name', array(
            'header'    => Mage::helper('adminhtml')->__('Name'),
            'index'     => 'name'
        ));


        $this->addColumn('url', array(
            'header'    => Mage::helper('adminhtml')->__('url'),
            'index'     => 'url'
        ));

        $this->addColumn('skus_str', array(
            'header'    => Mage::helper('adminhtml')->__('Skus'),
            'index'     => 'skus_str'
        ));

        $this->addColumn('action', array(
            'header'    => Mage::helper('adminhtml')->__('View'),
            'type' => 'recommend_url',
            'index'     => 'action'
        ));

        return parent::_prepareColumns();
    }
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}