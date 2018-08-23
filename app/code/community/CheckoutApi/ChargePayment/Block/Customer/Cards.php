<?php
/**
 * Block for My Account
 *
 * Class CheckoutApi_ChargePayment_Block_Customer_Cards
 *
 * @version 20151030
 */
class CheckoutApi_ChargePayment_Block_Customer_Cards  extends Mage_Core_Block_Template
{
    /**
     * Return true if Payment Method is Active
     *
     * @return bool
     *
     * @version 20151021
     */
    public function isActive() {
        return Mage::helper('chargepayment')->getConfigData(CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD, 'active');
    }

    /**
     * Return decorated array with customer card list
     *
     * @return array
     *
     * @version 20151030
     */
    public function getCustomerCardList() {
        $result         = array();

        $customerId     = Mage::getSingleton('customer/session')->getCustomer()->getId();

        if (empty($customerId)) {
            return $result;
        }

        $cardModel      = Mage::getModel('chargepayment/customerCard');
        $collection     = $cardModel->getCustomerCardList($customerId);

        if (!$collection->count()) {
            return $result;
        }

        foreach($collection as $index => $card) {
            $result[$index]['title']    = sprintf('xxxx-%s', $card->getCardNumber());
            $result[$index]['value']    = $cardModel->getCardSecret($card->getId(), $card->getCardNumber(), $card->getCardType());
            $result[$index]['type']     = $card->getCardType();
        }

        return $result;

    }
}