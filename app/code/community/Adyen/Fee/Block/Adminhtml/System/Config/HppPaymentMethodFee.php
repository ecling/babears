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

class Adyen_Fee_Block_Adminhtml_System_Config_HppPaymentMethodFee
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn('code', array(
            'label' => Mage::helper('adyen')->__('Payment Method Code'),
            'style' => 'width:250px',
        ));
        $this->addColumn('amount', array(
            'label' => Mage::helper('core')->__('Fixed costs'),
            'style' => 'width:100px',
        ));

        $this->addColumn('percentage', array(
            'label' => Mage::helper('core')->__('Variable costs (%)'),
            'style' => 'width:100px',
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('core')->__('Add Fee');

        parent::__construct();
    }
}
