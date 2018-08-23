<?php
class Martin_SalesReports_Adminhtml_Salesreports_OrderController extends Mage_Adminhtml_Controller_Action{
    public function preDispatch(){
        parent::preDispatch();
    }
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function gridAction(){
        
    }
    protected function _isAllowed(){
        return Mage::getSingleton('admin/session')->isAllowed('admin/salesreports/order');
    }
}