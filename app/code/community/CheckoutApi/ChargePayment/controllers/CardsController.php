<?php

/**
 * Controller for Edit Saved Cards List in My Account
 *
 * Class CheckoutApi_ChargePayment_CardsController
 *
 * @version 20151030
 */
class CheckoutApi_ChargePayment_CardsController extends Mage_Core_Controller_Front_Action
{
    /**
     * Customer Saved Card List
     *
     * @url chargepayment/cards/index
     *
     * @version 20151030
     */
    public function indexAction()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * For remove Customer Saved Card
     *
     * @version 20151030
     */
    public function removeAction() {
        $secretCard = $this->getRequest()->getParam('card');

        if (empty($secretCard)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('chargepayment')->__("Unable to delete Card."));
            $this->_redirect('chargepayment/cards');
        }

        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();

        if (empty($customerId)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('chargepayment')->__("Session Expired."));
            $this->_redirect('customer/account/login');
        }

        $cardModel = Mage::getModel('chargepayment/customerCard');
        $card = $cardModel->customerCardExists($secretCard, $customerId);

        if (empty($card)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('chargepayment')->__("Unable to delete Card."));
            $this->_redirect('chargepayment/cards');
        }

        $cardModel->removeCard($card->getId());

        Mage::getSingleton('core/session')->addSuccess(Mage::helper('chargepayment')->__("Card was removed."));
        $this->_redirect('chargepayment/cards');
    }
}
