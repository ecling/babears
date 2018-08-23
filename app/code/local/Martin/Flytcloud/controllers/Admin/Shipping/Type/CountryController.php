<?php

class Martin_Flytcloud_Admin_Shipping_Type_CountryController extends Mage_Adminhtml_Controller_Action
{
   public function saveAction()
   {
       try{
        $shippingTypeId=$this->getRequest()->getParam('shipping_type');
        $countryId=$this->getRequest()->getParam('country');
        $model=Mage::getModel('flytcloud/shipping_type_country')
                ->setShippingTypeId($shippingTypeId)
                ->setCountryId($countryId);
        $model->save();
       } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('*/shipping_type/new');return;
       }
       Mage::getSingleton('adminhtml/session')->addSuccess("add item sucess");
       $this->_redirect('*/shipping_type/index');return;
   }
   public function deleteAction()
   {
       try{
            $entityIds=$this->getRequest()->getParam('entity_id');
            if($entityIds){
                $collection=Mage::getModel('flytcloud/shipping_type_country')->getCollection()
                        ->addFieldToFilter('main_table.entity_id',array('in'=>$entityIds));
                foreach ($collection as $item){
                    $item->delete();
                }
            }
       }catch(Exception $e){
            $helper=Mage::helper('flytcloud');
            $country=$item->getCountry();
            $shippingType=$item->getShippingType();
            Mage::getSingleton('adminhtml/session')->addError("delete item failed: $country & $shippingType");
            $this->_redirect('*/shipping_type/index');return;
       }
       Mage::getSingleton('adminhtml/session')->addSuccess("delete sucesss");
       $this->_redirect('*/shipping_type/index');return;
   }
}

