<?php
class Martin_SalesReports_Adminhtml_Salesreports_StatusController extends Mage_Adminhtml_Controller_Action{
    public function preDispatch(){
        parent::preDispatch();
    }
    public function indexAction(){
        $this->_forward('grid');
    }
    public function gridAction(){
        $this->loadLayout();
        if ($this->getRequest()->getQuery('ajax')) {
            $block  = $this->getLayout()->createBlock('salesreports/adminhtml_product_grid', 'grid');
            $this->getResponse()->setBody($block->toHtml());
            return $this;
        }

        $this->renderLayout();
    }
    public function viewAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
    protected function _isAllowed(){
        return Mage::getSingleton('admin/session')->isAllowed('admin/salesreports/product');
    }
}