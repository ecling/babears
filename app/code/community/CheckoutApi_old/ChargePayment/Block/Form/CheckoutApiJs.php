<?php
/**
 * Payment Block for CheckoutApiJs, $_formBlockType
 *
 * Class CheckoutApi_ChargePayment_Block_Form_CheckoutApiJs
 *
 * @version 20151002
 */
class CheckoutApi_ChargePayment_Block_Form_CheckoutApiJs  extends Mage_Payment_Block_Form_Cc
{
    private $_paymentCode = CheckoutApi_ChargePayment_Helper_Data::CODE_CREDIT_CARD_JS;

    /**
     * Set template for checkout page
     *
     * @version 20160202
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('checkoutapi/chargepayment/form/checkoutapijs.phtml');
    }

    /**
     * Return true if secret key is correct
     *
     * @return bool
     *
     * @version 20160202
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
     *
     * @version 20160202
     */
    public function getPaymentTokenResult() {
        return Mage::getModel('chargepayment/creditCardJs')->getPaymentToken();
    }

    /**
     * Return Debug mode
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getDebugMode() {
        return Mage::getModel('chargepayment/creditCardJs')->isDebug();
    }

    /**
     * Return render mode
     *
     * @return int
     *
     * @version 20160203
     */
    public function getRenderMode() {
        return CheckoutApi_ChargePayment_Model_CreditCardJs::RENDER_MODE;
    }

    /**
     * Return name space for Checkout render
     *
     * @return string
     *
     * @version 20160203
     */
    public function getNamespace() {
        return CheckoutApi_ChargePayment_Model_CreditCardJs::RENDER_NAMESPACE;
    }

    /**
     * Return public key
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getPublicKey() {
        return  Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'publickey');
    }

    /**
     * Return Payment Mode
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getPaymentMode() {
        return  Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'payment_mode');
    }

    /**
     * Return url for logoUrl param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getLogoUrl() {
        return  Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'icon_url');
    }

    /**
     * Return color for themeColor param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getThemeColor() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'theme_color');
    }

    /**
     * Return bool for useCurrencyCode param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function isUseCurrencyCode() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'use_currency_code');
    }

    /**
     * Return title for title param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getTitle() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'form_title');
    }

    /**
     * Return color for widgetColor param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getWidgetColor() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'widget_color');
    }

    /**
     * Return color for formButtonColor param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getFormButtonColor() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'form_button_color');
    }

    /**
     * Return color for formButtonColorLabel param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getFormButtonColorLabel() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'form_button_color_label');
    }

    /**
     * Return shade for overlayShade param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getOverlayShade() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'overlay_shade');
    }

    /**
     * Return opacity for overlayOpacity param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getOverlayOpacity() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'overlay_opacity');
    }

    /**
     * Return bool for showMobileIcons param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function isShowMobileIcons() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'show_mobile_icons');
    }

    /**
     * Return size for widgetIconSize param
     *
     * @return mixed
     *
     * @version 20160203
     */
    public function getWidgetIconSize() {
        return Mage::helper('chargepayment')->getConfigData($this->_paymentCode, 'widget_icon_size');
    }

    /**
     * Return card form mode
     *
     * @return string
     *
     * @version 20160204
     */
    public function getCardFormMode() {
        return CheckoutApi_ChargePayment_Model_CreditCardJs::CARD_FORM_MODE;
    }

    /**
     * Return controller URL
     *
     * @return string
     *
     * @version 20160212
     */
    public function getControllerUrl() {
        $params     = array('form_key' => Mage::getSingleton('core/session')->getFormKey());
        $isSecure   = Mage::app()->getStore()->isCurrentlySecure();

        if ($isSecure){
            $secure = array('_secure' => true);
            $params = array_merge($params, $secure);

        }

        return $this->getUrl('chargepayment/api/place/', $params);
    }

    /**
     * Return Checkout.com script
     *
     * @return mixed
     *
     * @version 20160512
     */
    public function getJsPath() {
        return Mage::helper('chargepayment')->getJsPath();
    }

    /*
     * Check if customer is logged in
     *
     * */

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
            $result[$index]['title']    = sprintf('xxxx-%s', $card->getCardNumber());
            $result[$index]['value']    = $cardModel->getCardSecret($card->getId(), $card->getCardNumber(), $card->getCardType());
            $result[$index]['type']     = $card->getCardType();
        }

        return $result;
    }
}