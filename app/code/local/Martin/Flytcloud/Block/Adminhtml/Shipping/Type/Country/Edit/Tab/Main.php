<?php
      
Class Martin_Flytcloud_Block_Adminhtml_Shipping_Type_Country_Edit_Tab_Main extends
Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry('shipping_type_country');

        $form = new Varien_Data_Form();

        //$form->setHtmlIdPrefix('user_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('adminhtml')->__('Shpping type - Country')));

        if ($model->getId()) {
            $fieldset->addField('entity_id', 'hidden', array(
                'name' => 'entity_id',
            ));
        } else {
            if (! $model->hasData('is_active')) {
                $model->setIsActive(1);
            }
        }



            $fieldset->addField('shipping_type', 'select', array(
                'name'      => 'shipping_type',
                'label'     => Mage::helper('adminhtml')->__('Shpping Type'),
                'id'        => 'shipping_type',
                'title'     => Mage::helper('adminhtml')->__('Shpping Type'),
                'class'     => 'input-select',
                'style'        => 'width: 200px',
                'options'    => Mage::helper('flytcloud')->getShppingTypeOptions(),
            ));

            $fieldset->addField('country', 'select', array(
                'name'      => 'country',
                'label'     => Mage::helper('adminhtml')->__('目的地国家'),
                'id'        => 'country',
                'title'     => Mage::helper('adminhtml')->__('目的地国家'),
                'class'     => 'input-select',
                'style'        => 'width: 200px',
                'options'    => Mage::helper('flytcloud')->getCountryOptions(),
            ));
            
            
        $data = $model->getData();

        unset($data['password']);

        $form->setValues($data);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
