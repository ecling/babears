<?php


class Martin_Flytcloud_Admin_Shipping_TypeController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/acl')
            ->_addBreadcrumb($this->__('System'), $this->__('System'))
            ->_addBreadcrumb($this->__('Permissions'), $this->__('Permissions'))
            ->_addBreadcrumb($this->__('Users'), $this->__('Users'))
        ;
        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('System'))
             ->_title($this->__('Flytcloud'))
             ->_title($this->__('Shipping'))
             ->_title($this->__('Type'));

        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('flytcloud/shipping_type'))
            ->renderLayout();
    }
    
    public function gridAction()
    {   
        $this->loadLayout();
        $this->getResponse()
            ->setBody($this->getLayout()
            ->createBlock('flytcloud/shipping_type_grid')
            ->toHtml()
        );
    }
    
    public function newAction()
    {
        $this->_forward('edit');
    }
    public function editAction()
    {
        $this->_title($this->__('System'))
             ->_title($this->__('Flytcloud'))
             ->_title($this->__('Shipping'))
             ->_title($this->__('Type'));

        $id = $this->getRequest()->getParam('item_id');
        $model = Mage::getModel('flytcloud/shipping_type_country');

        if ($id) {
            $model->load($id);
            if (! $model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('This Item no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $this->_title($model->getId() ? $model->getName() : $this->__('New Item'));

        // Restore previously entered form data from session
        $data = Mage::getSingleton('adminhtml/session')->getItemData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register('shipping_type_country', $model);

        if (isset($id)) {
            $breadcrumb = $this->__('Edit Item');
        } else {
            $breadcrumb = $this->__('New Item');
        }
        $this->_initAction()
            ->_addBreadcrumb($breadcrumb, $breadcrumb);

        $this->getLayout()->getBlock('adminhtml.shippingTypeCountry.edit')
            ->setData('action', $this->getUrl('admin/shipping_type/save'));

        $this->renderLayout();
    }
}

