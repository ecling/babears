<?php
class Martin_SalesReports_Block_Adminhtml_Order_Num extends Mage_Adminhtml_Block_Template{ 
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
        
        $collection = Mage::getResourceModel('sales/order_collection');
        
        $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns("DATE_FORMAT(convert_tz(created_at,'+00:00','+08:00'),'%y%m%d') AS date")
            ->columns("SUM(base_grand_total) AS subtotal")
            ->where('created_at>=?',array('from'=>$from))
            ->where('created_at<?',array('to'=>$to))
            ->where("status='complete' OR status='processing'")
            ->group('date');

        return $collection;
    }
}