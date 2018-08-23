<?php
class Martin_SalesReports_Block_Adminhtml_Product_Ordered extends Mage_Adminhtml_Block_Template{
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
            //$to = strtotime($to)+3600*24;
            //$to = date('Y-m-d',$to);
            //$to = DateTime::createFromFormat('m/d/Y',$to);
            //$to = $to->format('Y-m-d');
            $to = $this->helper('salesreports')->convertDate($to,'en_US')->addDay(1)->subSecond(1)->toString('Y-MM-dd HH:mm:ss');
        }else{
            $to  = date('Y-m-d',time());
        }

        //$product_id = $this->getRequest()->getParam('id',null);

        $collection = Mage::getResourceModel('sales/order_item_collection');

        $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->joinLeft('sales_flat_order AS order','main_table.order_id=order.entity_id','')
            ->columns("DATE_FORMAT(convert_tz(order.created_at,'+00:00','+08:00'),'%y/%m/%d') AS date")
            ->columns("SUM(main_table.qty_ordered) AS subtotal")
            ->where('order.created_at>=?',array('from'=>$from))
            ->where('order.created_at<?',array('to'=>$to))
            ->where("order.status='complete' OR order.status='processing'")
            ->group('date');

        return $collection;
    }
}