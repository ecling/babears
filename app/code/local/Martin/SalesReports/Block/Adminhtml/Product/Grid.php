<?php
class Martin_SalesReports_Block_Adminhtml_Product_Grid extends Mage_Adminhtml_Block_Widget_Grid{
    public function __construct()
    {
        parent::__construct();
        $this->setId('customerGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('entity_id');
        $this->setSaveParametersInSession(true);

        $this->setTemplate('salesreports/product/grid.phtml');
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('salesreports/order_item_collection')
            ->addAttributeToSelect('product_id')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('qty_ordered')
            ->addAttributeToSelect('created_at');

        if($store = $this->getRequest()->getParam('store')){
            $collection->addAttributeToFilter('store_id',$store);     
        }
        
        if($from = $this->helper('salesreports')->getParam('from')){
            //$from = DateTime::createFromFormat('m/d/Y',$from);
            //$from = $from->format('Y-m-d');
            $from = $this->helper('salesreports')->convertDate($from,'en_US')->toString('Y-MM-dd HH:mm:ss');
        }else{
            $from = Mage::getModel('core/date')->date('m/d/Y',time());
            $from = $this->helper('salesreports')->convertDate($from,'en_US')->subDay(7)->toString('Y-MM-dd HH:mm:ss');
        }
        
        if($to = $this->helper('salesreports')->getParam('to')){
            //$to = strtotime($to)+3600*24;
            //$to = date('Y-m-d',$to);
            //$to = DateTime::createFromFormat('m/d/Y',$to);
            //$to = $to->format('Y-m-d');
            $to = $this->helper('salesreports')->convertDate($to,'en_US')->addDay(1)->subSecond(1)->toString('Y-MM-dd HH:mm:ss');
        }else{
            $to  = date('Y-m-d',time());
        }

        //$collection->addAttributeToFilter('created_at',array('from'=>$from,'to'=>$to));
            
        $collection->getSelect()
            ->columns("sum(qty_ordered) as num")
            ->joinLeft('sales_flat_order AS order','main_table.order_id=order.entity_id','')
            ->joinLeft('catalog_product_entity_varchar as img','main_table.product_id=img.entity_id and img.store_id=0 and img.attribute_id=85',array('image'=>'img.value'))
            ->where('order.created_at>=?',array('from'=>$from))
            ->where('order.created_at<?',array('to'=>$to))
            ->where("order.status='complete' OR order.status='processing'")
            ->order('num desc')
            ->group('product_id');

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('sku', array(
            'header'    => Mage::helper('customer')->__('sku'),
            'index'     => 'sku'
        ));

        $this->addColumn('image', array(
            'header'    => Mage::helper('customer')->__('Image'),
            'index'     => 'image',
            'type'      => 'image'
        ));
        
        $this->addColumn('name', array(
            'header'    => Mage::helper('customer')->__('Name'),
            'index'     => 'name'
        ));
        
        $this->addColumn('qty_ordered', array(
            'header'    => Mage::helper('customer')->__('Ordered Qty'),
            'index'     => 'num',
            'type'      => 'number'
        ));

        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('customer')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getProductId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('customer')->__('View Report'),
                        'url'       => array('base'=> '*/*/popup'),
                        'field'     => 'id',
                        'popup'     =>true

                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));

        $this->addColumn('url', array(
            'header'    => Mage::helper('customer')->__('View'),
            'type'      => 'product_url',
            'index'     => 'url',
            'field'       =>  'product_id'
        ));


        return parent::_prepareColumns();
    }
}