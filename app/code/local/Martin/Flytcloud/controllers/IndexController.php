<?php

class Martin_Flytcloud_IndexController extends Mage_Core_Controller_Front_Action
{
    public function testAction()
    {
        
        try{
            //$this->loadLayout();
            $order  = Mage::getModel('sales/order')->load(3102);
            $order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING)
                ->save();
            //echo $order->getPayment()->getMethodInstance()->getCode();
            //$this->renderLayout();
        } catch (Exception $ex) {
           var_dump($ex);exit;
        }
                        
    }
    public function indexAction(){//
        try{
            $roter=Mage::app()->getFrontController()->getRouter('standard');
            $modules=$roter->getModuleByFrontName('export');
            var_dump($modules);exit;   
        } catch (Exception $ex) {
           var_dump($ex);exit;
        }

    }
    public function productAction(){
        echo getcwd();
exit();
        Mage::app()->getStore()->setConfig();
        $products = Mage::getResourceModel('catalog/product_collection');
        
        $products->setStoreId(4)
        ->addAttributeToSelect('*')
        //->addStoreFilter(4)
        ->setPageSize($batch_max)
        ->setCurPage($count / $batch_max + 1)
        ->addUrlRewrite();
        
        print_r((string)$products->getSelect());
        exit();
        
        $stores  = Mage::app()->getStores();
        foreach($stores as $store){
            var_dump($store->getData());
        }
        exit();
        $date = time()-20*24*3600;
        $date = date('Y-m-d H:i:s',$date);
        $date = '2016-06-25 00:00:00';
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter($this->store_id);
        //    ->addFieldToFilter('created_at',array('gt'=>$date));
        $select = $collection->getSelect()
            ->where("e.created_at>'".$date."' or e.entity_id in (select product_id from (select product_id from sales_flat_order_item where store_id=1 group by product_id order by count(product_id) desc limit 50) as o)");
        //print_r((string)$collection->getSelect());
        $total_number_of_products = $collection->getSize();
        var_dump($total_number_of_products);
    }
}