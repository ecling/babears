<?php

class Martin_Flytcloud_Model_Resource_Order_Grid_Collection extends Mage_Sales_Model_Resource_Order_Grid_Collection
{
    protected function _beforeLoad() {
        $this->addShippingStatusCol();
        return parent::_beforeLoad();
    }
    
    public function addShippingStatusCol()
    {
        $selectF=$this->getSelect();
        
        $adapter=Mage::getSingleton('core/resource')->getConnection('read');

        $select=new Varien_Db_Select($adapter);
               
        $selectF->joinLeft(
                array('relation'=>$this->getTable('flytcloud/order_shipping_status')),
                'relation.`order`=`main_table`.entity_id',
                array('shippingStatusId'=>'relation.shipping_status','flytcloudNum'=>"relation.flytcloud_order_id"));
        $selectF->joinLeft(
                array('shippingStatus'=>$this->getTable('flytcloud/shipping_status')),
                'shippingStatus.entity_id=relation.shipping_status',
                array('shippingStatus'=>"IFNULL(shippingStatus.shipping_status,'')"));
        
        
        
        $where=$selectF->getPart(Zend_Db_Select::SQL_WHERE);
        
        $selectF->reset(strtolower(Zend_Db_Select::SQL_WHERE));

        $cloneSelect=clone $selectF;
        
        $selectF->reset();
        
        $selectF->from(array('main_table'=>$cloneSelect));
        if($where)
        {
           $selectF->where(implode(" ", $where));
        }
        return $this;
    }
}