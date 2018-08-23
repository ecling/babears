<?php

class Martin_Flytcloud_Admin_FlytcloudController extends Mage_Adminhtml_Controller_Action
{
    public function submitOrderAction()
    {
        try {
            $params=$this->getRequest()->getParams();

            $orderIds=isset($params['order_ids'])?$params['order_ids']:null;
            $helper=Mage::helper('flytcloud');
            $ordersDiffAddress=array();
            $submitFailed=false;

            $ordersToBeUploaded=array();
            
            if(!empty($orderIds)){
                $orderCollection=Mage::getModel('sales_resource/order_grid_collection');
                $orderCollection->addFieldToFilter('status','processing');
                      //  ->addFieldToFilter('shippingStatus',array('neq'=>'Submitted to Ship'));
                $orderCollection->addFieldToFilter('entity_id',array('in'=>$orderIds));
                $orderCollection->load();
                //Mage::log((string)$orderCollection->getSelect(),null,'select.log');
                if(count($orderCollection)>0)
                {
                    foreach($orderCollection as $order)
                    {
                        //Mage::log($order->getData('increment_id'),null,'orders.log');
                        if(!$helper->isSameAddress($order))
                        {
                            $ordersDiffAddress[]=$order->getData('increment_id');
                        }else{
                            $ordersToBeUploaded[]=$order;
                        }
                    }   
                }else{
                    Mage::getSingleton('adminhtml/session')->addError("没有符合条件（status为processing）的order被提交");
                    $this->_redirect('*/sales_order/index');return ;
                }

            }  else {
                Mage::getSingleton('adminhtml/session')->addError("没有order被选中");
                $this->_redirect('*/sales_order/index');return ;
            }

            if(!empty($ordersDiffAddress)){
                Mage::getSingleton('adminhtml/session')->addError("There are some orders whoes Billing Address is diffrient with Shipping Address :".implode(',', $ordersDiffAddress));
            }else{
                if(!empty($ordersToBeUploaded))
                {
                    foreach($ordersToBeUploaded as $order)
                    {
                        $orderNum=$order->getData('increment_id');
                        $return=$helper->submitOrderToFlytcloud($order);
                        if($return===true)
                        {
                            Mage::getSingleton('adminhtml/session')->addSuccess("\r\n order ".$orderNum." Submit success!");
                        }else{
                           Mage::getSingleton('adminhtml/session')->addError($return);
                        }
                    }
                }else{
                    Mage::getSingleton('adminhtml/session')->addError("没有符合条件的order被提交");
                    $this->_redirect('*/sales_order/index');return ;
                }
            }
        }catch(Exception $ex){
            Mage::getSingleton('adminhtml/session')->addError("There are something wrong,please check exception log.");
            Mage::logException($ex);
        }
       $this->_redirect('*/sales_order/index');
    }
}
