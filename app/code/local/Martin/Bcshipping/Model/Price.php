<?php
class Martin_Bcshipping_Model_Price extends Mage_Core_Model_Abstract
{    protected function _construct(){
        $this->_init('bcshipping/price');
    }
    public function loadByCountry($weight,$country){
        $this->_getResource()->loadByCountry($this,$weight,$country);
        return $this;
    }
}