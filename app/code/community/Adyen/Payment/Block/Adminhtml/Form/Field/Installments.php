<?php
/**
 * Magento
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
 * DISCLAIMER
 *
 * Adyen_Payment_Block_Adminhtml_Form_Field_Installments 	
 * is based on 
 * Mage_CatalogInventory_Block_Adminhtml_Form_Field_Minsaleqty
 * from Mage
 * 
 * via OSL rightfully adapted
 *
 * @category    Adyen
 * @package     Adyen_Payment
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml catalog inventory "Installments" field
 *
 * @category   Adyen
 * @package    Adyen_Payment
 * @author	   Adyen, Amsterdam
 */
class Adyen_Payment_Block_Adminhtml_Form_Field_Installments extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{


    /**
     * Prepare to render
     */
    protected function _prepareToRender()
    {
    	$this->addColumn('installment_currency', array(
    			'label' => Mage::helper('adyen')->__('Currency'),
    			'style' => 'width:100px',
    	));
        $this->addColumn('installment_boundary', array(
            'label' => Mage::helper('adyen')->__('Amount (incl.)'),
            'style' => 'width:100px',
        ));
        $this->addColumn('installment_frequency', array(
            'label' => Mage::helper('adyen')->__('Maximum Number of Installments'),
            'style' => 'width:100px',
        ));
        $this->addColumn('installment_interest', array(
            'label' => Mage::helper('adyen')->__('Interest Rate (%)'),
            'style' => 'width:100px',
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adyen')->__('Add Installment Boundary');
    }

}
