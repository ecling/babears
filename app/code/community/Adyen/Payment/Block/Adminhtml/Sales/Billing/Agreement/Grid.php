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
 
class Adyen_Payment_Block_Adminhtml_Sales_Billing_Agreement_Grid
    extends Mage_Sales_Block_Adminhtml_Billing_Agreement_Grid {

    /**
     * Prepare collection for grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {

        /** @var Adyen_Payment_Model_Resource_Billing_Agreement_Collection $collection */
        $collection = Mage::getResourceModel('adyen/billing_agreement_collection')
            ->addCustomerDetails();
        $collection->addNameToSelect();
        $this->setCollection($collection);

        call_user_func(array(get_parent_class(get_parent_class($this)), __FUNCTION__));
        return $this;
    }
    /**
     * Add columns to grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->removeColumn('customer_firstname');
        $this->removeColumn('customer_lastname');
        $this->addColumnAfter('agreement_label', array(
            'header'            => Mage::helper('sales')->__('Agreement Label'),
            'index'             => 'agreement_label',
            'type'              => 'text',
        ), 'status');

        $this->addColumnAfter('name', array(
            'header'            => Mage::helper('customer')->__('Name'),
            'index'             => 'name',
            'type'              => 'text',
        ), 'customer_email');

//        $status = $this->getColumn('status');
//        $status->setData('frame_callback', [$this, 'decorateStatus']);

        $createdAt = $this->getColumn('created_at');
        $createdAt->setData('index', 'created_at');

        $createdAt = $this->getColumn('updated_at');
        $createdAt->setData('index', 'updated_at');

        $this->sortColumnsByOrder();


        return $this;
    }

//    /**
//     * Decorate status column values
//     *
//     * @param string $value
//     * @param Mage_Index_Model_Process $row
//     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
//     * @param bool $isExport
//     *
//     * @return string
//     */
//    public function decorateStatus($value, $row, $column, $isExport)
//    {
//        $class = '';
//        switch ($row->getStatus()) {
//            case Adyen_Payment_Model_Billing_Agreement::STATUS_CANCELED :
//                $class = 'grid-severity-notice';
//                break;
//            case Adyen_Payment_Model_Billing_Agreement::STATUS_ACTIVE :
//                $class = 'grid-severity-notice';
//                break;
//        }
//        return '<span class="'.$class.'"><span>'.$value.'</span></span>';
//    }
}
