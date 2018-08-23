<?php

Class Martin_Bcshipping_Block_Adminhtml_Price_Edit_Form extends Mage_Adminhtml_Block_Widget_Form{
    public function __construct()
    {
        parent::__construct();
        $this->setId('rule_form');
        $this->setTitle(Mage::helper('tag')->__('Block Information'));
    }

    protected function _prepareForm()
    {
        $model = Mage::registry('shipping_price');

        $form = new Varien_Data_Form(
            array('id' => 'edit_form','action' => $this->getData('action'), 'method' => 'post')
        );

        //$form->setHtmlIdPrefix('user_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('adminhtml')->__('Shipping Method Rule')));

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', array(
                'name' => 'id',
            ));
        } else {
            if (! $model->hasData('is_active')) {
                $model->setIsActive(1);
            }
        }

        $fieldset->addField('country', 'select', array(
            'name'      => 'country',
            'label'     => Mage::helper('adminhtml')->__('目的地国家'),
            'id'        => 'country',
            'title'     => Mage::helper('adminhtml')->__('目的地国家'),
            'class'     => 'input-select',
            'style'        => 'width: 200px',
            'options'    => Mage::helper('flytcloud')->getCountryOptions(),
        ));

        $fieldset->addField('condition_num', 'text', array(
            'name' => 'condition_num',
            'label' => Mage::helper('tag')->__('Condition'),
            'title' => Mage::helper('tag')->__('Condition'),
            'required' => true
        ));

        $fieldset->addField('shipping_name', 'text', array(
            'name' => 'shipping_name',
            'label' => Mage::helper('tag')->__('Shipping Name'),
            'title' => Mage::helper('tag')->__('Shipping Name'),
            'required' => true
        ));

        $fieldset->addField('price', 'text', array(
            'name' => 'price',
            'label' => Mage::helper('tag')->__('Price'),
            'title' => Mage::helper('tag')->__('Price'),
            'required' => true
        ));

        $fieldset->addField('additional_price', 'text', array(
            'name' => 'additional_price',
            'label' => Mage::helper('tag')->__('Additional Price'),
            'title' => Mage::helper('tag')->__('Additional Price'),
            'required' => true
        ));

        $data = $model->getData();

        unset($data['password']);

        $form->setValues($data);
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}

