<?php
/**
 * Payment Block for CheckoutApiCard, $_formBlockType
 *
 * Class CheckoutApi_ChargePayment_Block_Form_CheckoutApiCard
 *
 * @version 20151002
 */
class CheckoutApi_ChargePayment_Block_Form_CheckoutApiCard  extends Mage_Payment_Block_Form_Cc
{
    /**
     * Set template for checkout page
     *
     * @version 20151002
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('checkoutapi/chargepayment/form/checkoutapicard.phtml');
    }

    /**
     * Return true if secret key is correct
     *
     * @return bool
     *
     * @version 20151021
     */
    public function isActive() {
        $helper     = Mage::helper('chargepayment');
        $secretKey  = $helper->getConfigData(CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD, 'secretkey');

        return !empty($secretKey) ? true : false;
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     *
     * @version 20151026
     */
    public function isCustomerLogged() {
        return Mage::getModel('chargepayment/creditCard')->getCustomerId();
    }

    /**
     * Return decorated array with customer card list
     *
     * @return array
     *
     * @version 20151026
     */
    public function getCustomerCardList() {
        $result         = array();

        $customerId     = Mage::getModel('chargepayment/creditCard')->getCustomerId();

        if (empty($customerId)) {
            return $result;
        }


        $cardModel      = Mage::getModel('chargepayment/customerCard');
        $collection     = $cardModel->getCustomerCardList($customerId);


        if (!$collection->count()) {
            return $result;
        }

        foreach($collection as $index => $card) {

            if($card->getSaveCard() == ''){
              continue;
            }

            $result[$index]['title']    = sprintf('xxxx-%s', $card->getCardNumber());
            $result[$index]['value']    = $cardModel->getCardSecret($card->getId(), $card->getCardNumber(), $card->getCardType());
            $result[$index]['type']     = $card->getCardType();
        }

        return $result;
    }

    /**
     * Display settings for select with CcTypes
     *
     * @return mixed
     *
     * @version 20160111
     */
    public function isVisibleCcType() {
        return Mage::getModel('chargepayment/creditCard')->getIsVisibleCcType();
    }


    /**
    * Get Save Card setting from config
    *
    **/
    public function isSaveCard(){
        return Mage::getModel('chargepayment/creditCard')->getSaveCardSetting();
    }
}