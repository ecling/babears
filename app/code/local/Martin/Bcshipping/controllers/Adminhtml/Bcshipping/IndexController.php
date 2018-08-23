<?php
class Martin_Bcshipping_Adminhtml_Bcshipping_IndexController extends
    Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales')
            ->_addBreadcrumb($this->__('bcshipping'), $this->__('Manage Shipping'))
        ;
        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('Manage Shipping'));

        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('bcshipping/adminhtml_price'))
            ->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()
            ->setBody($this->getLayout()
                ->createBlock('bcshipping/adminhtml_price_grid')
                ->toHtml()
            );
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $this->_title($this->__('Add New Rule'));

        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('bcshipping/price');

        if ($id) {
            $model->load($id);
            if (! $model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('This Item no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $this->_title($model->getId() ? $model->getCountry() : $this->__('New Item'));

        // Restore previously entered form data from session
        $data = Mage::getSingleton('adminhtml/session')->getItemData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register('shipping_price', $model);

        if (isset($id)) {
            $breadcrumb = $this->__('Edit Rule');
        } else {
            $breadcrumb = $this->__('New Rule');
        }
        $this->_initAction()
            ->_addBreadcrumb($breadcrumb, $breadcrumb);

        $this->_addContent(
            $this->getLayout()->createBlock('bcshipping/adminhtml_price_edit')
                ->setData('action', $this->getUrl('admin/shipping_type/save'))
        );

        $this->renderLayout();
    }

    public function saveAction(){
        if ($postData = $this->getRequest()->getPost()) {
            $data['country'] = $postData['country'];
            $data['condition_num'] = $postData['condition_num'];
            $data['shipping_name'] = $postData['shipping_name'];
            $data['price'] = $postData['price'];
            $data['additional_price'] = $postData['additional_price'];
            $model  = Mage::getModel('bcshipping/price')
                ->setData($data);

            try {
                $model->save();

                Mage::dispatchEvent('bcshipping_rule_save_after',array());

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('The Rule has been saved.'));
                Mage::getSingleton('adminhtml/session')->setTagData(false);

                return $this->_redirect('*/*/index/');

            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setRuleData($data);

                return $this->_redirect('*/*/edit', array('tag_id' => $model->getId(), 'store' => $model->getStoreId()));
            }
        }
        return $this->_redirect('*/*/index', array('_current' => true));
    }

    public function deleteAction(){
        if ($postData = $this->getRequest()->getPost()) {
            foreach($postData['id'] as $id){
                $model  = Mage::getModel('bcshipping/price')->load($id);
                if($model){
                    $model->delete();
                }
            }
        }

        Mage::dispatchEvent('bcshipping_rule_save_after',array());

        return $this->_redirect('*/*/index', array('_current' => true));
    }

    protected function _isAllowed(){
        return Mage::getSingleton('admin/session')->isAllowed('sales/bcshipping');
    }
}