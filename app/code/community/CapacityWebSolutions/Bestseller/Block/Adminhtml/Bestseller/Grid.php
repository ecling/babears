<?php
/***************************************************************************
 Extension Name	: Bestseller Products
 Extension URL	: http://www.magebees.com/magento-bestseller-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_Bestseller_Block_Adminhtml_Bestseller_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('bestsellerGrid');
		//$this->setDefaultSort('bestseller_id');
		$this->setDefaultSort('entity_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
		$this->setUseAjax(true);
	}

	protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }
	
	
	protected function _prepareCollection()
	{
		$store = $this->_getStore();
		$product_skus = $this->getProductSkus();
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('type_id')
			->addAttributeToFilter('sku', array('in' => $product_skus));
			$adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
            $collection->addStoreFilter($store);
            $collection->joinAttribute(
                'name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $adminStore
            );
			$collection->joinAttribute(
                'custom_name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'status',
                'catalog_product/status',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'visibility',
                'catalog_product/visibility',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'price',
                'catalog_product/price',
                'entity_id',
                null,
                'left',
                $store->getId()
            );
		
		$this->setCollection($collection);
		return parent::_prepareCollection();
	}

	protected function _prepareColumns()
	{
		/* $this->addColumn('bestseller_id', array(
			'header'    => Mage::helper('bestseller')->__('ID'),
			'align'     => 'right',
			'width'		=> '50px',
			'index'     => 'bestseller_id',
		));
		 */
		$this->addColumn('entity_id',
            array(
                'header'=> Mage::helper('catalog')->__('ID'),
                'width' => '50px',
                'type'  => 'number',
                'index' => 'entity_id',
        ));
        $this->addColumn('name',
            array(
                'header'=> Mage::helper('catalog')->__('Name'),
                'index' => 'name',
			));

        $store = $this->_getStore();
        if ($store->getId()) {
            $this->addColumn('custom_name',
                array(
                    'header'=> Mage::helper('catalog')->__('Name in %s', $store->getName()),
                    'index' => 'custom_name',
            ));
        }

        $this->addColumn('type',
            array(
                'header'=> Mage::helper('catalog')->__('Type'),
                'width' => '100px',
                'index' => 'type_id',
                'type'  => 'options',
                'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
        ));
      
        $this->addColumn('sku',
            array(
                'header'=> Mage::helper('catalog')->__('SKU'),
                'width' => '80px',
                'index' => 'sku',
        ));

        $store = $this->_getStore();
        $this->addColumn('price',
            array(
                'header'=> Mage::helper('catalog')->__('Price'),
                'type'  => 'price',
                'currency_code' => $store->getBaseCurrency()->getCode(),
                'index' => 'price',
        ));
		
		return parent::_prepareColumns();
	}
	
	public function getSkusArr($element){
		return $element['sku'];
	}
	
	public function getProductSkus(){
		$model = Mage::getModel('bestseller/bestseller')->getCollection();
		$product_skus=array_map(array($this,"getSkusArr"), $model->getData());
		return $product_skus;
	}
	
	//public function getRowUrl($row)
	//{
		//return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	//}
	
	protected function _prepareMassaction()
    {
        $this->setMassactionIdField('bestseller_id');
        $this->getMassactionBlock()->setFormFieldName('bestseller');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('bestseller')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('bestseller')->__('Are you sure?')
        ));

		return $this;
    }
	
	public function getGridUrl()
    {
		return $this->getUrl('*/*/grid', array('_current'=>true));
    }

}