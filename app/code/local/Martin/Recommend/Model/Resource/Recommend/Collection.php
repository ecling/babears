<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/3 0003
 * Time: 14:31
 */
class Martin_Recommend_Model_Resource_Recommend_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract{
    protected function _construct()
    {
        $this->_init('recommend/recommend');
    }
}