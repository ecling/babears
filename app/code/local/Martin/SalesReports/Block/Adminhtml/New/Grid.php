<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/10 0010
 * Time: 14:09
 */

class Martin_SalesReports_Block_Adminhtml_New_Grid extends Mage_Adminhtml_Block_Widget_Grid{
    protected $_defaultFilter   = array();
    protected $_from;
    protected $_to;
    public function __construct()
    {
        $from = date('m/d/Y',time()-3600*24*7);
        $to = date('m/d/Y',time());

        $this->_from = Mage::helper('salesreports')->convertDate($from,'en_US')->toString('MM/dd/Y');
        $this->_to = Mage::helper('salesreports')->convertDate($to,'en_US')->toString('MM/dd/Y');

        $this->_defaultFilter = array('created_at'=>array(
            'from'=>$this->_from,
            'to'=>$this->_to,
            'locale'=>'en_US'
        ));
        parent::__construct();
        $this->setId('customerGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('entity_id');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('image');

        if($store = $this->getRequest()->getParam('store')){
            $collection->addAttributeToFilter('store_id',$store);
        }

        /*
        if($from = $this->helper('salesreports')->getParam('from')){
            $from = DateTime::createFromFormat('m/d/Y',$from);
            $from = $from->format('Y-m-d');
        }else{
            $from = date('Y-m-d',time()-3600*24*7);
        }

        if($to = $this->helper('salesreports')->getParam('to')){
            $to = strtotime($to)+3600*24;
            $to = date('Y-m-d',$to);
            //$to = DateTime::createFromFormat('m/d/Y',$to);
            //$to = $to->format('Y-m-d');
        }else{
            $to  = date('Y-m-d',time());
        }
        */
        //$from = date('Y-m-d',time()-3600*24*7);
        //$to  = date('Y-m-d',time());

        //$collection->addAttributeToFilter('created_at',array('from'=>$from,'to'=>$to));

        $collection->getSelect()
            ->columns("SUM(IF(order.status='complete' OR order.status='processing',oi.qty_ordered,0)) as num")
            ->joinLeft('sales_flat_order_item as oi','oi.product_id=e.entity_id','')
            //->joinLeft('sales_flat_order AS order',"oi.order_id=order.entity_id and (order.status='complete' or order.status='processing')",'')
            ->joinLeft('sales_flat_order AS order',"oi.order_id=order.entity_id",'')
            ->joinLeft("cataloginventory_stock_status_idx as stock","stock.product_id=oi.product_id and stock.website_id=1",'ROUND(9999-stock.qty) as stock_num')
            //->where('order.created_at>=?',array('from'=>$this->_from))
            //->where('order.created_at<?',array('to'=>$this->_to))
            //->where("order.status='complete' OR order.status='processing'")
            //->order('num desc')
            ->group('e.entity_id');
        $this->setCollection($collection);

        $this->_preparePage();

        $columnId = $this->getParam($this->getVarNameSort(), $this->_defaultSort);
        $dir      = $this->getParam($this->getVarNameDir(), $this->_defaultDir);
        $filter   = $this->getParam($this->getVarNameFilter(), null);

        if (is_null($filter)) {
            $filter = $this->_defaultFilter;
        }

        if (is_string($filter)) {
            $data = $this->helper('adminhtml')->prepareFilterString($filter);
            if(!isset($data['created_at'])||!isset($data['created_at']['from'])){
                $data['created_at'] = array(
                    'from'=>$this->_from,
                    'to'=>$this->_to,
                    'locale'=>'en_US'
                );
            }
            $this->_setFilterValues($data);
        }
        else if ($filter && is_array($filter)) {
            $this->_setFilterValues($filter);
        }
        else if(0 !== sizeof($this->_defaultFilter)) {
            $this->_setFilterValues($this->_defaultFilter);
        }

        if(is_null($data)){
            $data = $this->_defaultFilter;
        }
        $from = $data['created_at']['from'];
        $to = $data['created_at']['to'];

        $from = $this->helper('salesreports')->convertDate($from,'en_US')->toString('Y-MM-dd HH:mm:ss');
        $to = $this->helper('salesreports')->convertDate($to,'en_US')->addDay(1)->subSecond(1)->toString('Y-MM-dd HH:mm:ss');

        $collection->getSelect()
            ->where('order.created_at>=?',array('from'=>$from))
            ->where('order.created_at<?',array('to'=>$to));

        if (isset($this->_columns[$columnId]) && $this->_columns[$columnId]->getIndex()) {
            $dir = (strtolower($dir)=='desc') ? 'desc' : 'asc';
            if($columnId=='qty_ordered') {
                $this->getCollection()->getSelect()
                    ->order("num $dir");
            }
            $this->_columns[$columnId]->setDir($dir);
            $this->_setCollectionOrder($this->_columns[$columnId]);
        }

        if (!$this->_isExport) {
            $this->getCollection()->load();
            $this->_afterLoadCollection();
        }

        //return parent::_prepareCollection();
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

        $this->addColumn('qty_ordered', array(
            'header'    => Mage::helper('customer')->__('Ordered Qty'),
            'index'     => 'num',
            'type'      => 'number'
        ));

        $this->addColumn('stock', array(
            'header'    => Mage::helper('customer')->__('Stock'),
            'index'     => 'stock_num',
            'type'      => 'number'
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('customer')->__('Created At'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'timezone' => true
        ));

        $this->addColumn('days', array(
            'header'    => Mage::helper('customer')->__('Days'),
            'type'      => 'days',
            'index'     => 'days',
        ));

        $this->addColumn('url', array(
            'header'    => Mage::helper('customer')->__('View'),
            'type'      => 'product_url',
            'index'     => 'url',
            'field'       =>  'entity_id'
        ));

        return parent::_prepareColumns();
    }
}