<?php
class Martin_SalesReports_Block_Adminhtml_Product_Filter_Form extends Mage_Adminhtml_Block_Widget_Form{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('salesreports/order/num/form.phtml');
        $this->setDestElementId('edit_form');
        $this->setShowGlobalIcon(false);
    }
    protected function _prepareForm(){
        $store_id = $this->getRequest()->getParam('store');
        $id = $this->getRequest()->getParam('id');
        $actionUrl = $this->getUrl('*/*/*/',array('store'=>$store_id,'id'=>$id));
        $form = new Varien_Data_Form(
            array('id' => 'filter_form', 'action' => $actionUrl, 'method' => 'post')
        );
        $htmlIdPrefix = 'sales_report_';
        
        $from = $this->helper('salesreports')->getParam('from');
        $to = $this->helper('salesreports')->getParam('to');
        
        $form->setHtmlIdPrefix($htmlIdPrefix);
        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('reports')->__('Filter')));

        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        
        $fieldset->addField('from', 'date', array(
            'name'      => 'from',
            'format'    => $dateFormatIso,
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
            'label'     => Mage::helper('reports')->__('From'),
            'title'     => Mage::helper('reports')->__('From'),
            'value'     => $from,
            'required'  => true
        ));

        $fieldset->addField('to', 'date', array(
            'name'      => 'to',
            'format'    => $dateFormatIso,
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
            'label'     => Mage::helper('reports')->__('To'),
            'title'     => Mage::helper('reports')->__('To'),
            'value'     => $to,
            'required'  => true
        ));
        
        
        $form->setUseContainer(true);
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}