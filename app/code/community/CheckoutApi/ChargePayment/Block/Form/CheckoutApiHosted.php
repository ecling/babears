<?php
/**
 * Payment Block for CheckoutApiJs, $_formBlockType
 *
 * Class CheckoutApi_ChargePayment_Block_Form_CheckoutApiHosted
 */
class CheckoutApi_ChargePayment_Block_Form_CheckoutApiHosted  extends Mage_Payment_Block_Form_Cc
{
    private $_paymentCode = CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_HOSTED;

    /**
     * Set template for checkout page
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('checkoutapi/chargepayment/form/checkoutapihosted.phtml');
    }

    /**
     * Return true if secret key is correct
     *
     * @return bool
     */
    public function isActive() {
        $helper     = Mage::helper('chargepayment');
        $secretKey  = $helper->getConfigData($this->_paymentCode, 'secretkey');

        return !empty($secretKey) ? true : false;
    }

    /**
     * Get Payment Token Result
     *
     * @return mixed
     */
    public function getPaymentTokenResult() {
        return Mage::getModel('chargepayment/hosted')->getPaymentToken();
    }

    /**
     * Return bool for useCurrencyCode param
     *
     * @return mixed
     */
    public function isUseCurrencyCode() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'use_currency_code');
    }

    /**
     * Return render mode
     *
     * @return int
     */
    public function getRenderMode() {
        return CheckoutApi_ChargePayment_Model_Hosted::RENDER_MODE;
    }

    /**
     * Return name space for Checkout render
     *
     * @return string
     */
    public function getNamespace() {
        return CheckoutApi_ChargePayment_Model_Hosted::RENDER_NAMESPACE;
    }

    /**
     * Return public key
     *
     * @return mixed
     */
    public function getPublicKey() {
        return  Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'publickey');
    }

    /**
     * Return Payment Mode
     *
     * @return mixed
     */
    public function getPaymentMode() {
        return  Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'payment_mode');
    }

    /**
     * Return card form mode
     *
     * @return string
     */
    public function getCardFormMode() {
        return CheckoutApi_ChargePayment_Model_Hosted::CARD_FORM_MODE;
    }

    /**
     * Return Checkout.com script
     *
     * @return mixed
     */
    public function getHostedJsPath() {
        return Mage::helper('chargepayment')->getHostedJsPath();
    }

    public function isCustomerLogged() {

        return Mage::getModel('chargepayment/hosted')->getCustomerId();
    }

    public function getCustomerCardList() {
        $result         = array();

        $customerId     = Mage::getModel('chargepayment/hosted')->getCustomerId();

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
        return Mage::getModel('chargepayment/hosted')->getSaveCardSetting();
    }
}