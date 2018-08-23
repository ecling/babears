<?php
/**
 * Payment Info Block for CheckoutApiJs, $_infoBlockType
 *
 * Class CheckoutApi_ChargePayment_Block_Info_CheckoutApiHosted
 */
class CheckoutApi_ChargePayment_Block_Info_CheckoutApiHosted  extends Mage_Payment_Block_Info_Cc
{
    /**
     * Removed cart type
     *
     * @return string
     */
    public function getCcTypeName()
    {
        return false;
    }
}