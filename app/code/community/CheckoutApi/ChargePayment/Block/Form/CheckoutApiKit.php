<?php
/**
 * Payment Block for Checkout Kit, $_formBlockType
 *
 * Class CheckoutApi_ChargePayment_Block_Form_CheckoutApiKit
 *
 * @version 20160502
 */
class CheckoutApi_ChargePayment_Block_Form_CheckoutApiKit  extends Mage_Payment_Block_Form_Cc
{
    /**
     * @var
     */
    private $_helper;

    /**
     * Set template for checkout page
     *
     * @version 20160502
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('checkoutapi/chargepayment/form/checkoutapikit.phtml');
        $this->_helper = Mage::helper('chargepayment');
    }

    /**
     * Return true if secret key is correct
     *
     * @return bool
     *
     * @version 20160502
     */
    public function isActive() {
        $secretKey = $this->_helper->getConfigData(CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_KIT, 'secretkey');
        $publicKey = $this->getPublicKey();

        return !empty($secretKey) && !empty($publicKey) ? true : false;
    }

    /**
     * Return Stored Public Key
     *
     * @return mixed
     *
     * @version 20160502
     */
    public function getPublicKey() {
        return $this->_helper->getConfigData(CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_KIT, 'publickey');
    }

    /**
     * Return Debug Mode
     *
     * @return mixed
     *
     * @version 20160502
     */
    public function getDebugMode() {
        return Mage::getModel('chargepayment/creditCardKit')->isDebug();
    }

    /**
     * Return Customer Email
     *
     * @return mixed
     *
     * @version 20160504
     */
    public function getCustomerEmail() {
        return $this->_helper->getCustomerEmail();
    }

    /**
     * Return Checkout.com script
     *
     * @return mixed
     *
     * @version 20160512
     */
    public function getKitJsPath() {
        return Mage::helper('chargepayment')->getKitJsPath();
    }

    public function isCustomerLogged() {

        return Mage::getModel('chargepayment/creditCardJs')->getCustomerId();
    }

    /*
     * return customer's saved cards
     * */
    public function getCustomerCardList() {
        $result         = array();

        $customerId     = Mage::getModel('chargepayment/creditCardJs')->getCustomerId();

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
    * Get Save Card setting from config
    *
    **/
    public function isSaveCard(){
        return Mage::getModel('chargepayment/creditCardKit')->getSaveCardSetting();
    }
}