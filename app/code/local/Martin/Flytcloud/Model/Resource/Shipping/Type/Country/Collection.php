<?php

class Martin_Flytcloud_Model_Resource_Shipping_Type_Country_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('flytcloud/shipping_type_country');
    }
    protected function _beforeLoad() {
        parent::_beforeLoad();
        
        $select=$this->getSelect();
        $select->joinLeft(
                array('shippintType'=>$this->getTable('flytcloud/shipping_type')),
                'shippintType.`entity_id`=`main_table`.shipping_type_id',
                array('shipping_type'=>'shippintType.shipping_type','country'=>'main_table.country_id'));

        return $this;
    }
    protected function _afterLoad() 
{
        parent::_afterLoad();
        $helper=Mage::helper('flytcloud');
        foreach($this as $item){
            $countryName=$helper->getCountryById($item->getCountry());
            $item->setCountry($countryName);
        }
        return $this;
    }
}
