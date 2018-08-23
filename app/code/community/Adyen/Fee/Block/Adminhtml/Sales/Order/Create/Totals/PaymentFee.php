<?php
/**
 * Created by PhpStorm.
 * User: rikt
 * Date: 12/11/15
 * Time: 3:27 PM
 */


class Adyen_Fee_Block_Adminhtml_Sales_Order_Create_Totals_PaymentFee
    extends Mage_Adminhtml_Block_Sales_Order_Create_Totals_Default
{

    protected $_template = 'adyen/fee/order/create/totals/paymentfee.phtml';
    protected $_taxConfig;

    /**
     * Initialize the taxConfig model
     */
    protected function _construct()
    {
        parent::_construct();

        // define helper
        $this->_taxConfig = Mage::getModel('adyen_fee/tax_config');
    }

    /**
     * Check if we need display payment fee include and exclude tax
     *
     * @return bool
     */
    public function displayBoth()
    {
        return $this->_taxConfig->displayCartPaymentFeeBoth($this->getStore());
    }

    /**
     * Check if we need display payment fee include tax
     *
     * @return bool
     */
    public function displayIncludeTax()
    {
        return $this->_taxConfig->displayCartPaymentFeeInclTax($this->getStore());
    }

    /**
     * Get payment fee amount include tax
     *
     * @return float
     */
    public function getPaymentFeeIncludeTax()
    {
        return $this->getTotal()->getAddress()->getPaymentFeeAmount() + $this->getTotal()->getAddress()->getPaymentFeeTax();
    }

    /**
     * Get payment fee amount exclude tax
     *
     * @return float
     */
    public function getPaymentFeeExcludeTax()
    {
        return $this->getTotal()->getAddress()->getPaymentFeeAmount();
    }

    /**
     * Get label for payment fee include tax
     *
     * @return float
     */
    public function getIncludeTaxLabel()
    {
        return $this->helper('adyen_fee')->__('Payment Fee Incl. Tax');
    }

    /**
     * Get label for payment fee exclude tax
     *
     * @return float
     */
    public function getExcludeTaxLabel()
    {
        return $this->helper('adyen_fee')->__('Payment Fee Excl. Tax');
    }
}