<?php
class Martin_SalesReports_Block_Adminhtml_Product_Num extends Mage_Adminhtml_Block_Template{ 
    public function getCollection(){
        if($from = $this->helper('salesreports')->getParam('from')){
            //$from = DateTime::createFromFormat('m/d/Y',$from);
            //$from = $from->format('Y-m-d');
            $from = $this->helper('salesreports')->convertDate($from,'en_US')->toString('Y-MM-dd HH:mm:ss');
        }else{
            $from = Mage::getModel('core/date')->date('m/d/Y',time());
            $from = $this->helper('salesreports')->convertDate($from,'en_US')->subDay(7)->toString('Y-MM-dd HH:mm:ss');
        }
        
        if($to = $this->helper('salesreports')->getParam('to')){
            //$to = DateTime::createFromFormat('m/d/Y',$to);
            //$to = $to->format('Y-m-d');
            $to = $this->helper('salesreports')->convertDate($to,'en_US')->addDay(1)->subSecond(1)->toString('Y-MM-dd HH:mm:ss');
        }else{
            $to  = date('Y-m-d',time());
        }
        
        $product_id = $this->getRequest()->getParam('id',null);
        
        $collection = Mage::getResourceModel('sales/order_item_collection');
        
        $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns("DATE_FORMAT(main_table.created_at,'%y%m%d') AS date")
            ->columns("SUM(main_table.qty_ordered) AS subtotal")
            ->joinLeft('sales_flat_order as o','o.entity_id=main_table.order_id','')
            ->where("o.status='complete' OR o.status='processing'")
            ->where('o.created_at>=?',array('from'=>$from))
            ->where('o.created_at<=?',array('to'=>$to))
            ->where('main_table.product_id=?',$product_id)
            ->group('date');
        
        return $collection;
    }

    public function getCountry(){
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
            $to = $this->helper('salesreports')->convertDate($to,'en_US')->addDay(1)->subSecond(1)->toString('Y-MM-dd HH:mm:ss');
        }else{
            $to  = date('Y-m-d',time());
        }

        $product_id = $this->getRequest()->getParam('id',null);

        $collection = Mage::getResourceModel('sales/order_item_collection');

        $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns('oa.country_id')
            ->columns('main_table.sku')
            ->columns('main_table.price')
            ->columns('p.created_at')
            ->columns("SUM(qty_ordered) AS qty")
            ->joinLeft('sales_flat_order_address as oa','main_table.order_id=oa.parent_id','')
            ->joinLeft('sales_flat_order as o','o.entity_id=main_table.order_id','')
            ->joinLeft('catalog_product_entity as p','p.entity_id=main_table.product_id','')
            ->where("o.status='complete' OR o.status='processing'")
            ->where("oa.address_type='shipping'")
            ->where('o.created_at>=?',array('from'=>$from))
            ->where('o.created_at<=?',array('to'=>$to))
            ->where('main_table.product_id=?',$product_id)
            ->group('oa.country_id');

        return $collection;
    }
}