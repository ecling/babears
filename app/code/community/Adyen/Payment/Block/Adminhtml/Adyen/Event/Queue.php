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

/**
 * Adminhtml sales orders block
 */

class Adyen_Payment_Block_Adminhtml_Adyen_Event_Queue extends Mage_Adminhtml_Block_Widget_Grid_Container {

    /**
     * Instructions to create child grid
     *
     * @var string
     */
    protected $_blockGroup = 'adyen';
    protected $_controller = 'adminhtml_adyen_event_queue';


    /**
     * Set header text and remove "add" btn
     */
    public function __construct()
    {
        $this->_headerText = Mage::helper('adyen')->__('Adyen Notification Queue');
        parent::__construct();
        $this->_removeButton('add');
    }



}