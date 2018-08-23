<?php
/***************************************************************************
 Extension Name	: Bestseller Products
 Extension URL	: http://www.magebees.com/magento-bestseller-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_Bestseller_Adminhtml_BestsellerController extends Mage_Adminhtml_Controller_Action {

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('cws');
		return $this;
	}   
 
	public function indexAction() {
		$this->_initAction()
			->renderLayout();
	}
	
	public function newAction() {
		$this->_forward('edit');
	}
 
	public function editAction() {
		$id = $this->getRequest()->getParam('id');
		$model  = Mage::getModel('bestseller/bestseller')->load($id);
		
		if ($model->getBestsellerId() || $id == 0) {
			
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('bestseller_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('cws');
			
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Best Seller Products'), Mage::helper('adminhtml')->__('Best Seller Products'));
			
			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('bestseller/adminhtml_bestseller_edit'))
				->_addLeft($this->getLayout()->createBlock('bestseller/adminhtml_bestseller_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bestseller')->__('Best Seller Products does not exist'));
			$this->_redirect('*/*/');
		}
	}
 
	public function saveAction() {
		$product_skus = array();
		$product_ids = array();
		
		if ($data = $this->getRequest()->getPost()) {
			
			$id = $this->getRequest()->getParam('id');	
			
			if (isset($data['product_sku'])) {
				$data['product_sku'] = explode(', ',$data['product_sku']);
				if (is_array($data['product_sku'])) {
					$product_skus = array_unique($data['product_sku']);
				}
			}
			
			foreach($product_skus as $sku){
				$product_ids[$sku] = Mage::getModel("catalog/product")->getIdBySku($sku);
			}
			
			try {
				//save product skus
				$model = Mage::getModel('bestseller/bestseller');
					$prd_data = $model->getCollection(); 
					$prd_data->walk('delete');  
						
				foreach($product_skus as $sku){
					if($sku){
						$model->setData('sku',$sku);
						$model->setData('product_id',$product_ids[$sku]);
						$model->save();
						$model->unsetData();
					}
				}
			
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('bestseller')->__('Best Seller Products was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $model->getBestsellerId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bestseller')->__('Unable to find Best Seller Products to save'));
        $this->_redirect('*/*/');
	}
	
	public function massDeleteAction() {
        $bestseller_ids = $this->getRequest()->getParam('bestseller');
		
        if(!is_array($bestseller_ids)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($bestseller_ids as $bestseller_id) {
                    $bestseller = Mage::getModel('bestseller/bestseller')->load($bestseller_id,'product_id');
                    $bestseller->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($bestseller_ids)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
 	
	public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
               $this->getLayout()->createBlock('bestseller/adminhtml_bestseller_grid')->toHtml()
        );
    }
	
}