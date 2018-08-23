<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/29 0029
 * Time: 10:33
 */

class Martin_Bcshipping_Model_Observer
{
    public function updatePriceCrontabByConfig(Varien_Event_Observer $observer)
    {
        if($observer->getSection()=='bcshipping'){
            $this->_updatePriceCrontab();
        }
        return $this;
    }

    public function updatePriceCrontabByRule(){
        $this->_updatePriceCrontab();
        return $this;
    }

    protected function _updatePriceCrontab(){
        $adapter = Mage::getSingleton('core/resource')->getConnection('core_write');
        $set = array('node' => 0);
        $where = 'crontab_id=1';
        $adapter->update('crontab', $set, $where);
        return $this;
    }
}
