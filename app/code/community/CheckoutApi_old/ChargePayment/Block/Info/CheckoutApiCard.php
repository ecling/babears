<?php
/**
 * Payment Info Block for CheckoutApiCard, $_infoBlockType
 *
 * Class CheckoutApi_ChargePayment_Block_Info_CheckoutApiCard
 *
 * @version 20151002
 */
class CheckoutApi_ChargePayment_Block_Info_CheckoutApiCard  extends Mage_Payment_Block_Info_Cc
{
    /**
     * Retrieve credit card type name
     *
     * Removed cart type
     *
     * @return string
     */
    public function getCcTypeName()
    {
        $checkoutApiCardId  = $this->getInfo()->getCheckoutApiCardId();
        $cardType           = $this->getInfo()->getCcType();
        $isVisibleCcType    = Mage::getModel('chargepayment/creditCard')->getIsVisibleCcType();

        if ($isVisibleCcType && !empty($cardType)) {

            return parent::getCcTypeName();
        }

        if (!empty($checkoutApiCardId) && !empty($cardType)) {
            return $cardType;
        }

        return false;
    }
}