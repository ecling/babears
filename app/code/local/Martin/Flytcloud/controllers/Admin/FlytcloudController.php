<?php

class Martin_Flytcloud_Admin_FlytcloudController extends Mage_Adminhtml_Controller_Action
{
    /**
     * 数据库更新：
     * insert into `bellecat`.`flytcloud_shipping_status` ( `entity_id`, `shipping_status` ) values (  '',  'Processing' )
     * insert into `bellecat`.`flytcloud_shipping_status` ( `entity_id`, `shipping_status` ) values (  '',  'Failed' )
     * 
     */
    public function submitOrderAction()
    {
        try {
            $params=$this->getRequest()->getParams();

            $orderIds=isset($params['order_ids'])?$params['order_ids']:null;
            $helper=Mage::helper('flytcloud');
            
            $adpter = Mage::getSingleton('core/resource')->getConnection('core_write');
            
            $adpter->beginTransaction();
            
            if(!empty($orderIds)){
                $orderCollection=Mage::getModel('sales_resource/order_grid_collection');
                $orderCollection->addFieldToFilter('status','processing');
                      //  ->addFieldToFilter('shippingStatus',array('neq'=>'Submitted to Ship'));
                $orderCollection->addFieldToFilter('entity_id',array('in'=>$orderIds));
                $orderCollection->getSelect()
                    ->where("shippingStatusId is null or shippingStatusId=4");
                $orderCollection->load();
                  
                if($orderCollection->getSize()>0)
                {
                    foreach($orderCollection as $order)
                    {
                        $order_id = $order->getId();
                        $order_status_result = $adpter->query("select `order` from flytcloud_order_shipping_status where `order`=".$order_id);
                        $order_status = $order_status_result->fetch();
                        if($order_status){
                            $set = array('shipping_status'=>'3');
                            $where = "`order`=".$order_id;
                            $adpter->update('flytcloud_order_shipping_status',$set,$where);
                        }else{
                            $row = array('order'=>$order_id,'shipping_status'=>'3');
                            $adpter->insert('flytcloud_order_shipping_status',$row);
                        }   
                    }
                    
                    Mage::getSingleton('adminhtml/session')->addSuccess("\r\n ".$orderCollection->getSize()."个订单 Submit success!");
                       
                }else{
                    Mage::getSingleton('adminhtml/session')->addError("没有符合条件（status为processing）的order被提交");
                    $this->_redirect('*/sales_order/index');return ;
                }

            }  else {
                Mage::getSingleton('adminhtml/session')->addError("没有order被选中");
                $this->_redirect('*/sales_order/index');return ;
            }
            $adpter->commit();
        }catch(Exception $ex){
            $adpter->rollBack();
            Mage::getSingleton('adminhtml/session')->addError("There are something wrong,please check exception log.");
            Mage::logException($ex);
        }
       $this->_redirect('*/sales_order/index');
    }
    
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/flytcloud');
    }
}
