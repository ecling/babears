<?php

class Xtwocn_Debug_IndexController extends Mage_Core_Controller_Front_Action{
    
    public function indexAction()
    {
        var_dump(get_class_methods('Mage_Sales_Model_Resource_Order_Shipment_Collection'));
        var_dump(Mage::app()->getConfig()->getNode('global/events'));
    }
    public function testAction(){
        try{
            $order=Mage::getModel('sales/order')->load('400000006','increment_id');
            var_dump($order->debug());
            $track=array('carrier_code'=>'custom','number'=>'123456');
            Mage::dispatchEvent("order_update_load_to_shipper_success",array('order'=>$order,'tracks'=>array($track)));
            
        } catch (Exception $ex) {
             var_dump($ex);exit;
        }

    }
    
    public function quoteTestAction(){
        $checkoutSession=Mage::getSingleton('checkout/session');
        
        $checkoutSession->getQuote()->save();
        var_dump($checkoutSession->getQuote()->debug());exit;
        
        
        $productCollection=Mage::getModel('catalog/product')->getCollection()
                ->addCategoryFilter(Mage::getModel('catalog/category')->load(6));
        $tmp=null;
        foreach($productCollection as $product){
            //var_dump($product->debug());
            if($product->getId()==340){
                $tmp=$product;
            }
        }
        
        $tmp->setData('status',0);
        foreach($productCollection as $product){
            var_dump($product->debug());
        }
        exit;
    }
    
    
    public function transferOrderAction(){
       // $quote=
    }
    
    protected function _transferQuote(Mage_Sales_Model_Quote $quote){
        $typeOnepage = Mage::getSingleton('checkout/type_onepage');
    }
    
    public function backtraceAction(){
        $backtrace=  debug_backtrace();
      //  var_dump($backtrace);exit;
        $str='';
        $timer=0;
        function handleArray($array,$prefix){
            $prefix.='--';
            
            foreach($array as $key=>$item){
                $str.=$prefix."$key=>\r\n";
                if(is_object($item)){
                    $item="object:".get_class($item);
                }elseif(is_array($item)){
                    $item=handleArray($item, $prefix);
                }
                
                $str.=$prefix.$item."\r\n";
            }
            return $str;
        }
        $obj=Mage::getModel('catalog/product');
        $arr=array(
            'niah',
            array(
                'shide',
                $obj
                ),
        );
        echo "<pre>",handleArray($arr),"</pre>";
        exit;   
        foreach($backtrace as $item){
            $str.="\r\n\r\n $timer";
            foreach($item as $key=>$data){
                if(is_object($data)){
                    $str.="\r\n$key => ".get_class($data);
                }elseif(is_array($data)){
                    $str.="\r\n$key : \r\n   ";
//                    foreach($data as $argsItem){
//                        
//                    }
                    $str.=json_encode($data);
                }else{
                   $str.="\r\n$key => $data";
                }
                
            }
            $timer++;
        }
        echo "<pre>",$str,"</pre>";exit;
    }
    
    public function demoAction(){
       $product = Mage::getModel('index/event')->load(306);
       var_dump($product->getDataObject());exit;
    }


    
    public function getDataFromTxt(){
        $path=Mage::app()->getConfig()->getNode('global/auto/cms/datafolder');
        if($path){
            $path=Mage::getBaseDir().DS.$path.DS."block";
            if(is_dir($path)){
                $dir=dir($path);
                while(false!==($entry=$dir->read())){
                    if(!preg_match('/^\.{1,2}$/',$entry)){
                        $files[]=$path.DS.$entry;
                    }
                    
                }
                if(!empty($files)){
                    foreach($files as $file){
                        var_dump($file);
                        $data=trim(file_get_contents($file));
                        $data=json_decode($data,true);
                        $blocksData[]=$data;
                    }
                    var_dump($blocksData);
                }
            }
        }
    }
    public function shippingAction(){
        echo "nihao";
                $layout=Mage::app()->getLayout();
        echo $layout->createBlock("core/template", "shipping")
                ->setTemplate('martin/shipping/shippingtablerate.phtml')
                ->toHtml();
        
    }
    public function pageAction(){
      //  var_dump(Mage::app()->getStore()->getStoreId());exit;
        $helper=Mage::helper('auto/cms_page');
        
        $helper->addNewPages();
        
        $helper=Mage::helper('auto/cms_data');
        $helper->autoAddCMSBlocks();
        
        exit;

        $page=Mage::getModel('cms/page');
        $data=array(
            'title'=>'test',
            'identifier'=>'test',
            'stores'=>1,
            'is_active'=>1,
            'under_version_control'=>0,
            'content'=>"这是测试内容！！！",
            'root_template'=>'one_column',
        );
       $page->setData($data)->save();
       var_dump($page->getId());exit;
    }
    
    public function variableAction(){
        $data=array(
            'hotline-opening'=>
            array("code"=>"hotline-opening","name"=>"Hotline Opening","store_id"=>4,"plain_value"=>"周一至周五：8:00 - 18:00 \r\n周六至周日：10:00 - 18:00","html_value"=>"周一至周五：8:00 - 18:00 \r\n周六至周日：10:00 - 18:00"),
//            'company'=>
//            array("code"=>"company","name"=>"Company","store_id"=>4,"plain_value"=>"","html_value"=>""),
//            'chief-executive'=>
//            array("code"=>"chief-executive","name"=>"Chief Executive","store_id"=>4,"plain_value"=>"","html_value"=>""),
//            'street'=>
//            array("code"=>"","name"=>"","store_id"=>4,"plain_value"=>"","html_value"=>""),
//            'zip'=>
//            array("code"=>"","name"=>"","store_id"=>4,"plain_value"=>"","html_value"=>""),
//            'city'=>
//            array("code"=>"","name"=>"","store_id"=>4,"plain_value"=>"","html_value"=>""),
            'register-court'=>
            array("code"=>"register-court","name"=>"Register Court","store_id"=>4,"plain_value"=>"吉森地方法院","html_value"=>"吉森地方法院"),
//            'register-number'=>
//            array("code"=>"","name"=>"","store_id"=>4,"plain_value"=>"","html_value"=>""),
//            'chief-executive'=>
//            array("code"=>"","name"=>"","store_id"=>4,"plain_value"=>"","html_value"=>""),
//            'ust-id'=>
//            array("code"=>"","name"=>"","store_id"=>4,"plain_value"=>"","html_value"=>""),
        );
        foreach($data as $item){
            $obj=Mage::getModel('core/variable')->setData($item)->save();
        }
    }
    
}
