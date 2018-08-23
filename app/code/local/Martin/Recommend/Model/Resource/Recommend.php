<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/3 0003
 * Time: 14:30
 */
class Martin_Recommend_Model_Resource_Recommend extends Mage_Core_Model_Resource_Db_Abstract{
    protected function _construct()
    {
        $this->_init('recommend/recommend','recommend_id');
    }
}