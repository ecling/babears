<?php

/**
 * Adyen Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category	Adyen
 * @package	Adyen_Payment
 * @copyright	Copyright (c) 2011 Adyen (http://www.adyen.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */

class Adyen_Payment_Block_Adminhtml_Adyen_Event_Queue_Grid extends Mage_Adminhtml_Block_Widget_Grid {


    public function __construct() {
        parent::__construct();
        $this->setId('adyen_adyen_event_queue_grid');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }


    /**
     * Prepare grid collection object
     *
     * @return Adyen_Payment_Block_Adminhtml_Adyen_Event_Queue_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('adyen/event_queue_collection');
        $this->setCollection($collection);
        if (!$this->getParam($this->getVarNameSort())) {
            $collection->setOrder('event_queue_id', 'desc');
        }
        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return Adyen_Payment_Block_Adminhtml_Adyen_Event_Queue_Grid
     */
    protected function _prepareColumns()
    {
        $eventQueue = Mage::getModel('adyen/event_queue');
//
        $helper = Mage::helper('adyen');

        $this->addColumn('event_queue_id', array(
            'header' => $helper->__('ID #'),
            'index'  => 'event_queue_id'
        ));

        $this->addColumn('psp_reference', array(
            'header' => $helper->__('PSP Reference'),
            'index'  => 'psp_reference'
        ));


        $this->addColumn('adyen_event_code', array(
            'header' => $helper->__('Adyen Event Code'),
            'index'  => 'adyen_event_code'
        ));

        $this->addColumn('increment_id', array(
            'header' => $helper->__('Order #'),
            'index'  => 'increment_id'
        ));

        $this->addColumn('attempt', array(
            'header' => $helper->__('Attempt'),
            'index'  => 'attempt'
        ));

        $this->addColumn('created_at', array(
            'header' => $helper->__('Created At'),
            'index'  => 'created_at',
            'type' => 'datetime'
        ));


        $this->addColumn('action',
            array(
                'header'    => Mage::helper('adyen')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('adyen')->__('Execute'),
                        'url'     => array('base'=>'*/adyen_event_queue/execute'),
                        'field'   => 'event_queue_id',
                        'data-column' => 'action',
                    ),
                    array(
                        'caption' => Mage::helper('adyen')->__('Delete'),
                        'url'     => array('base'=>'*/adyen_event_queue/delete'),
                        'field'   => 'event_queue_id',
                        'data-column' => 'action',
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
            ));


        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('event_queue_id');
        $this->getMassactionBlock()->setFormFieldName('queue_id');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'=> Mage::helper('adyen')->__('Delete'),
            'url'  => $this->getUrl('*/*/massDelete', array('' => '')),        // public function massDeleteAction() in Adyen_Payment_Adminhtml_Adyen_Event_QueueController
            'confirm' => Mage::helper('adyen')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('execute', array(
            'label'=> Mage::helper('adyen')->__('Execute'),
            'url'  => $this->getUrl('*/*/massExecute', array('' => '')),        // public function massDeleteAction() in Adyen_Payment_Adminhtml_Adyen_Event_QueueController
            'confirm' => Mage::helper('adyen')->__('Are you sure?')
        ));

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}