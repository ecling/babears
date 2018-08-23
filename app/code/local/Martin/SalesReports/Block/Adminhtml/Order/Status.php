<?php
class Martin_SalesReports_Block_Adminhtml_Order_Status extends Mage_Adminhtml_Block_Template{
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

        $collection = Mage::getResourceModel('sales/order_collection');

        $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns("DATE_FORMAT(convert_tz(main_table.created_at,'+00:00','+08:00'),'%y/%m/%d') AS date")
            ->joinLeft('sales_flat_order as ps',"main_table.entity_id=ps.entity_id and ps.status='processing'",'COUNT(ps.entity_id) AS processing')
            ->joinLeft('sales_flat_order as cp',"main_table.entity_id=cp.entity_id and cp.status='complete'",'COUNT(cp.entity_id) AS complete')
            ->joinLeft('sales_flat_order as pd',"main_table.entity_id=pd.entity_id and pd.status='pending'",'COUNT(pd.entity_id) AS pending')
            ->joinLeft('sales_flat_order as pp',"main_table.entity_id=pp.entity_id and pp.status='pending_payment'",'COUNT(pp.entity_id) AS pending_payment')
            ->joinLeft('sales_flat_order as cc',"main_table.entity_id=cc.entity_id and cc.status='canceled'",'COUNT(cc.entity_id) AS canceled')
            ->joinLeft('sales_flat_order as cs',"main_table.entity_id=cs.entity_id and cs.status='closed'",'COUNT(cs.entity_id) AS closed')
            ->where('main_table.created_at>=?',array('from'=>$from))
            ->where('main_table.created_at<=?',array('to'=>$to))
            ->group('date');

        return $collection;
    }
}