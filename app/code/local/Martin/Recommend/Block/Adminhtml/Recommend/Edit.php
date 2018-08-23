<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/3 0003
 * Time: 11:28
 */

class Martin_Recommend_Block_Adminhtml_Recommend_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_blockGroup='recommend';
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_recommend';

        parent::__construct();

        $this->_updateButton('save', 'label', Mage::helper('adminhtml')->__('Save'));
        $this->_updateButton('delete', 'label', Mage::helper('adminhtml')->__('Delete'));
    }

    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', array('_current'=>true));
    }

    public function getHeaderText()
    {
        if (Mage::registry('shipping_price')->getId()) {
            return Mage::helper('adminhtml')->__("Edit Item '%s'", $this->escapeHtml(Mage::registry('shipping_price')->getId()));
        }
        else {
            return Mage::helper('adminhtml')->__('New Recommend');
        }
    }
}