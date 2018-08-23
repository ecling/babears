<?php
class Martin_SalesReports_Adminhtml_Salesreports_NewController extends Mage_Adminhtml_Controller_Action{
    public function preDispatch(){
        parent::preDispatch();
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('salesreports')
            ->_addBreadcrumb($this->__('salesreports'), $this->__('New Product Sales Report'))
        ;
        return $this;
    }

    public function indexAction(){
        $this->loadLayout();
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
        }
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('salesreports/adminhtml_new'))
            ->renderLayout();
    }
    public function gridAction(){
        $this->loadLayout();

        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('salesreports/adminhtml_new_grid')->toHtml()
        );
    }
    public function viewAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
    protected function _isAllowed(){
        return Mage::getSingleton('admin/session')->isAllowed('admin/salesreports/new');
    }
}