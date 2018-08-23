<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/3 0003
 * Time: 11:28
 */

class Martin_Recommend_Block_Adminhtml_Recommend_Edit_Form extends Mage_Adminhtml_Block_Widget_Form{
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

        $fieldset->addField('name', 'text', array(
            'name' => 'name',
            'label' => Mage::helper('tag')->__('Name'),
            'title' => Mage::helper('tag')->__('Name'),
            'required' => true
        ));

        $fieldset->addField('url', 'text', array(
            'name' => 'url',
            'label' => Mage::helper('tag')->__('Category Url'),
            'title' => Mage::helper('tag')->__('Category Url'),
            'required' => true
        ));

        $fieldset->addField('SKUS', 'text', array(
            'name' => 'skus',
            'label' => Mage::helper('tag')->__('SKUS'),
            'title' => Mage::helper('tag')->__('SKUS'),
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