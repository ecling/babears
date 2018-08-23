<?php
class Martin_Bcshipping_Model_Resource_Price extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('bcshipping/price','id');
    }

    public function loadByCountry($model,$weight,$country){
        if($weight && $country){
            $read = $this->_getReadAdapter();
            $select = $read->select();

            $select->from($this->getMainTable())
                ->where('country = :country')
                ->where('condition_num < :weight')
                ->order('condition_num DESC')
                ->limit(1);
            $data = $read->fetchRow($select, array('country' => $country,'weight'=>$weight));
            $model->setData(( is_array($data) ) ? $data : array());
        }else{
            return false;
        }
    }
}