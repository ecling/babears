<?php
/***************************************************************************
	@extension	: New Products.
	@copyright	: Copyright (c) 2015 Capacity Web Solutions.
	( http://www.capacitywebsolutions.com )
	@author		: Capacity Web Solutions Pvt. Ltd.
	@support	: magento@capacitywebsolutions.com	
***************************************************************************/

class CapacityWebSolutions_Newproducts_Adminhtml_NewproductsController extends Mage_Adminhtml_Controller_Action {

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
		$model  = Mage::getModel('newproducts/newproducts')->load($id);
		
		if ($model->getMostviewedId() || $id == 0) {
			
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('newproducts_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('cws');
			
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('New Products'), Mage::helper('adminhtml')->__('New Products'));
			
			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('newproducts/adminhtml_newproducts_edit'))
				->_addLeft($this->getLayout()->createBlock('newproducts/adminhtml_newproducts_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('newproducts')->__('New Products does not exist'));
			$this->_redirect('*/*/');
		}
	}
	
	public function getSkusArr($element){
		return $element['sku'];
	}
 
	public function getProductSkus($store_id=0){
		$featuredCollection = Mage::getModel('newproducts/newproducts')->getCollection()->addFieldToFilter('store_id', array(array('finset' => $store_id)));
		$product_skus=array_map(array($this,"getSkusArr"), $featuredCollection->getData());
		return $product_skus;
	}
	
	public function saveAction() {
		if ($data = $this->getRequest()->getPost()) {
			$id = $this->getRequest()->getParam('id');	
			$store_id = $data['store_id'];
			$product_skus = array();
			if (isset($data['product_sku'])) {
				$product_skus  = explode(', ',$data['product_sku']);
			}
			
			try {
				$store_ids_arr = array();
				
				//if uncheck product sku
				$old_skus_arr = $this->getProductSkus($store_id);
				$sku_for_remove_arr = array();
				$sku_for_remove_arr = array_diff($old_skus_arr,$product_skus);
				if($sku_for_remove_arr){
					
					foreach ($sku_for_remove_arr as $sku) {
						$newproducts = Mage::getModel('newproducts/newproducts')->load($sku,'sku');
						
						if(!$store_id){//for all store views
							$newproducts->delete();
						}else{
							$new_store_ids_arr = array();
							$old_store_ids = $newproducts->getData('store_id');
							$old_store_ids_arr = explode(",",$old_store_ids);
							$new_store_ids_arr = array_diff($old_store_ids_arr,array($store_id));
							$new_store_ids = implode(",",$new_store_ids_arr);
													
							if(count($new_store_ids_arr)==1){
							
								$newproducts->delete();
							}else{
								$newproducts->setData('sku',$sku);
								$newproducts->setData('store_id',$new_store_ids);
								$newproducts->save();
							}
						}
					}
				}
				if(!empty($data['product_sku'])){
					if(!$store_id){//for save sku all store views
						$store_ids_arr = Mage::helper('newproducts')->getStoreViewIds();//get all storeview ids;
						array_push($store_ids_arr,0);
						$store_ids = implode(",",$store_ids_arr);
						foreach($product_skus as $sku){
							if(!in_array($sku,$old_skus_arr)){
								$model = Mage::getModel('newproducts/newproducts')->load($sku,'sku');
								$model->setData('sku',$sku);
								$model->setData('store_id',$store_ids);
								$model->save();
							}
						}
					}else{//for specific storeview
						$store_ids_arr[] = $store_id;
						array_push($store_ids_arr,0);
						foreach($product_skus as $sku){
							$model = Mage::getModel('newproducts/newproducts')->load($sku,'sku');
							if($model->getId()){//sku exist
								$old_store_ids = $model->getData('store_id');
								$old_store_ids_arr = explode(",",$old_store_ids);
								$new_store_ids_arr = array_unique(array_merge($old_store_ids_arr,$store_ids_arr));
								$new_store_ids = implode(",",$new_store_ids_arr);
								$model->setData('sku',$sku);
								$model->setData('store_id',$new_store_ids);
								$model->save();
							}else{
								$store_ids = implode(",",$store_ids_arr);
								$model->setData('sku',$sku);
								$model->setData('store_id',$store_ids);
								$model->save();
							}
						}
					}
				}
								
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('newproducts')->__('New Products was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					//$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
					$this->_redirectReferer();
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
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('newproducts')->__('Unable to find New Products to save'));
        $this->_redirect('*/*/');
	}
	
	public function massDeleteAction() {
        $newproducts_ids = $this->getRequest()->getParam('newproducts');
		$store_id = $this->getRequest()->getParam('store');
		$skus_for_remove_arr = array();
				
		if(!is_array($newproducts_ids)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
				foreach($newproducts_ids as $id){
					$skus_for_remove_arr[$id] = Mage::getModel('catalog/product')->load($id)->getSku();
				}
				foreach ($skus_for_remove_arr as $sku) {
                    $newproducts = Mage::getModel('newproducts/newproducts')->load($sku,'sku');
					if($newproducts->isEmpty() && $store_id!=0){
						Mage::getSingleton('adminhtml/session')->addError("Cannot delete. Please switch to All Store Views.");
						$this->_redirectReferer();
						return;
					}
					if(!$store_id){//for all store views
						$newproducts->delete();
					}else{
						$new_store_ids_arr = array();
						$old_store_ids = $newproducts->getData('store_id');
						$old_store_ids_arr = explode(",",$old_store_ids);
						$new_store_ids_arr = array_diff($old_store_ids_arr,array($store_id));
						$new_store_ids = implode(",",$new_store_ids_arr);
												
						if(count($new_store_ids_arr)==1){
						
							$newproducts->delete();
						}else{
							$newproducts->setData('sku',$sku);
							$newproducts->setData('store_id',$new_store_ids);
							$newproducts->save();
						}
					}
				}
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted.', count($newproducts_ids)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
		$this->_redirectReferer();
    }
	
	public function massDelete2Action() {
        $newproducts_ids = $this->getRequest()->getParam('newproducts');
		$store_id = $this->getRequest()->getParam('store');
		$new_ids = array();
		$new_skus = array();
		
		if(!is_array($newproducts_ids)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($newproducts_ids as $newproducts_id) {
                    $newproducts = Mage::getModel('newproducts/newproducts')->load($store_id,'store_id');
					if($newproducts->isEmpty() && $store_id!=0){
						Mage::getSingleton('adminhtml/session')->addError("Cannot delete. Please switch to All Store Views.");
						$this->_redirectReferer();
						return;
					}
					$product_ids = explode(', ',$newproducts->getProductId());
					
					$new_ids = array_diff($product_ids,$newproducts_ids);
					foreach($new_ids as $id){
						$new_skus[$id] = Mage::getModel('catalog/product')->load($id)->getSku();
					}
					$ids = implode(", ",$new_ids);
					$skus = implode(", ",$new_skus);
					
					$mostviewed->setData('sku',$skus);
					$mostviewed->setData('product_id',$ids);
					$mostviewed->save();
		             //  $mostviewed->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted.', count($newproducts_ids)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
		$this->_redirectReferer();
    }  
 	
	public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
               $this->getLayout()->createBlock('newproducts/adminhtml_newproducts_grid')->toHtml()
        );
    }
	
}