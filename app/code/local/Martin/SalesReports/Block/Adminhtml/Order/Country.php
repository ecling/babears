<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/9 0009
 * Time: 11:42
 */

class Martin_SalesReports_Block_Adminhtml_Order_Country extends Mage_Adminhtml_Block_Template{
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
            ->columns("DATE_FORMAT(convert_tz(main_table.created_at,'+00:00','+08:00'),'%Y-%m-%d') AS da")
            ->columns("oa.country_id as country")
            ->columns("COUNT(main_table.entity_id) AS cnt")
            ->columns("SUM(main_table.base_grand_total) AS total")
            ->joinLeft('sales_flat_order_address AS oa','main_table.entity_id=oa.parent_id','')
            ->where('main_table.created_at>=?',array('from'=>$from))
            ->where('main_table.created_at<?',array('to'=>$to))
            ->where("main_table.status='complete' OR main_table.status='processing'")
            ->where("oa.address_type='shipping'")
            ->group('da')
            ->group('country')
            ->order('da ASC')
            ->order('country ASC');

        return $collection;
    }
}