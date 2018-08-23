<?php
/**
 * Payment Info Block for CheckoutApiJs, $_infoBlockType
 *
 * Class CheckoutApi_ChargePayment_Block_Info_CheckoutApiJs
 *
 * @version 20160203
 */
class CheckoutApi_ChargePayment_Block_Info_CheckoutApiJs  extends Mage_Payment_Block_Info_Cc
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