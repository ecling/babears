<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/10 0010
 * Time: 14:09
 */
class Martin_SalesReports_Block_Adminhtml_New extends Mage_Adminhtml_Block_Widget_Grid_Container{
    protected $_blockGroup = 'salesreports';
    protected $_controller = 'adminhtml_new';

    public function __construct()
    {
        parent::__construct();
        $this->_headerText = Mage::helper('customer')->__('新品销量报表');
        $this->removeButton('add');
    }
}