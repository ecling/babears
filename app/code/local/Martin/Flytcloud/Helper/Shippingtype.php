<?php

class Martin_Flytcloud_Helper_Shippingtype extends Mage_Core_Helper_Abstract
{
    protected $_shippintTypeCountryCollection;
    protected $_defualtShippingType;


    public function getShippingTypeByOrder($order)
   {
        $address=$order->getShippingAddress();
       if( $address && $address->getData('country_id'))
       {
         return  $this->getShippingTypeByCountry($address->getData('country_id'));
       }
       return $this->getDefaultShippingType();
   }
   public function getDefaultShippingType()
   {
       if(!$this->_defualtShippingType)
       {
            if(!$code = Mage::getStoreConfig('flytcloudgeneral/default/shipping_type')){
                $code=Martin_Flytcloud_Model_Shipping_Type::DEFAULT_TYPE_CODE;
            }
            $shippingType=Mage::getModel('flytcloud/shipping_type')->load($code,'shipping_type_code');
            $this->_defualtShippingType= $shippingType->setCountryId('');  
       }
       return $this->_defualtShippingType;
   }
   
   protected function _getRelationCollection()
   {
       if(!$this->_shippintTypeCountryCollection)
       {
            $shippingTypeColleciton=Mage::getModel('flytcloud/shipping_type')->getCollection();
            $select=$shippingTypeColleciton->getSelect();
            $select->join(
                array('relation' => $shippingTypeColleciton->getTable('flytcloud/shipping_type_country')),
                'main_table.entity_id = relation.shipping_type_id',
                'country_id'
            );
            $select->reset(Varien_Db_Select::COLUMNS)
                    ->columns(array("shipping_type",'shipping_type_code','country_id'=>'relation.country_id'));
            
            $this->_shippintTypeCountryCollection=$shippingTypeColleciton->load();
       }
       return $this->_shippintTypeCountryCollection;
   }
   public function getShippingTypeByCountry($countryId)
   {
        $collection=$this->_getRelationCollection();
       // $shippingTypeColleciton->addFieldToFilter('relation.country_id',$countryId);
        if(count($collection))
        {
           foreach($collection as $item)
           {
               //var_dump($item->getData());
               if($item->getData('country_id')==$countryId)  return $item; 
           } 
        }

        return $this->getDefaultShippingType();


   }
}
