<?php

class Martin_Export_ExportController extends Mage_Core_Controller_Front_Action
{
    public function testAction()
    {

    }

    protected function _checkDateAction($date)
    {
        $dateArr= explode('-', $date);
        return checkdate($dateArr[1],$dateArr[2],$dateArr[0]);
    }
    protected function _validatePW($password)
    {

        //$path='';
        //$pw=Mage::app()->getConfig()->getStoresConfigByPath($path);
        //return (MD5($password)==$pw)?true:false;
        //$pw=Mage::app()->getConfig()->getNode('martinexport/password');
        $pw=Mage::getStoreConfig("purchaselist/account/password");
        return MD5($password)==$pw?true:false;
    }


    public function filterPostAction()
    {
        try{
            $startDate=$this->getRequest()->getParam('startDate');
            $endDate=$this->getRequest()->getParam('endDate');
            $sufTime=" 23:59:59";
            $endDateTime=$endDate.$sufTime;
            $password=$this->getRequest()->getParam('password');
            $stores=$this->getRequest()->getParam('stores');
            if($this->_validatePW($password))
            {
                if($this->_checkDateAction($startDate) & $this->_checkDateAction($endDate)){
                    $filename="purchase-list-$startDate-$endDate.xlsx";
                    $helper=Mage::helper('martinexport/order');

                    /*
                    $orderCollection=Mage::getModel('sales/order')->getCollection();
                    $orderCollection->addFieldToFilter('created_at',array('from'=>$startDate,'to'=>$endDateTime))
                            ->addFieldToFilter('status','processing');
                    if($stores){
                        $orderCollection->addFieldToFilter('store_id',array('in'=>$stores));
                    }
                    */
                    
                    //直接根据时间获取产品数据
                    $collection = Mage::getResourceModel('sales/order_item_collection')
                        ->join(array('o'=>'sales/order'),'o.entity_id=main_table.order_id','')
                        ->addFieldToFilter('o.created_at',array('from'=>$startDate,'to'=>$endDateTime))
                        ->addFieldToFilter('o.status',array('in'=>array('processing','complete')))
                        ;
                    
                    $collection->getSelect()
                        ->join(array('p'=>'catalog_product_entity_varchar'),'p.entity_id=main_table.product_id AND p.attribute_id=85 AND p.store_id=0','value as image');
                    
                    if($stores){
                        $collection->addFieldToFilter('o.store_id',array('in'=>$stores));
                    }
                    
                    //print_r((string)$collection->getSelect());
                    //exit();
                    
                    $helper->setExcelValue("A1","时间段");
                    $helper->setExcelValue("B1","$startDate ~ $endDate");
                    $href=$helper->exportToExcel($collection,$filename);
                    echo <<<html
                    <div style="text-align:center"> <a href='{$href}'> 点击下载{$filename}</a> </div>
html;
                }else{
                    $errorMsg=  "请输检查并入正确的日期！  您输入的起始日期为： $startDate     $endDate ";
                }
            }else{
                $errorMsg= "密码验证失败，请输入正确的密码！";
            }
            if($errorMsg){
                Mage::getSingleton('core/session')->setData('error',$errorMsg);
                $this->_redirect('*/*/index');
            }  
        } catch (Exception $ex) {
                var_dump($ex);exit;
        }

    }
    
    public function indexAction()
    {
        $this->loadLayout();
        $layout=$this->getLayout();
        $filterFormBlock=$layout->createBlock('core/template')->setTemplate('martinexport/filterForm.phtml');
        
        $storeCollection=Mage::getModel('core/store')->getCollection()
                ->addFieldToFilter('code',array('neq'=>'admin'));
        $filterFormBlock->setStoreCollection($storeCollection);
        $error=Mage::getSingleton('core/session')->getError();
        if($error){
            echo "<div style='text-align:center;color:red'>$error</div>";
            Mage::getSingleton('core/session')->unsetData('error');
        }
        echo $filterFormBlock->toHtml();
    }
    
}
