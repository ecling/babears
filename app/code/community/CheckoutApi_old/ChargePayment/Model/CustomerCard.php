<?php

/**
 * Class for Saved credit cards
 *
 * Class CheckoutApi_ChargePayment_Model_CustomerCard
 *
 * @version 20151026
 */
class CheckoutApi_ChargePayment_Model_CustomerCard extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('chargepayment/customercard');
    }

    /**
     * For save Customer Credit Card Data
     *
     * @param Varien_Object $payment
     * @param $response
     * @return bool
     * @throws Mage_Core_Exception
     *
     * @version 20151026
     */
    public function saveCard(Varien_Object $payment, $response) {

        // use local variable to get metadata before use
        // due to $response->getMetadata()['integration_type'] 
        // is not compatible to PHP 5.3 or before.
        $_metadata = $response->getMetadata();
        $integrationType = $_metadata['integration_type'];

        if($integrationType == 'JS'){
            $customerId = Mage::getModel('chargepayment/creditCardJs')->getCustomerId();
            $last4 = $response->getCard()->getLast4();
        } elseif($integrationType == 'API'){
            $customerId = Mage::getModel('chargepayment/creditCard')->getCustomerId();
            $last4      = $payment->getCcLast4();
        }elseif($integrationType == 'KIT'){
            $customerId = Mage::getModel('chargepayment/creditCardKit')->getCustomerId();
            $last4 = $response->getCard()->getLast4();
        }

        if (empty($customerId)){
            // SOAP mobile
            $customerId = $payment->getData('cc_owner');
        }

        $cardId     = $response->getCard()->getId();
        $cardType   = $response->getCard()->getPaymentMethod();

        if (empty($customerId) || empty($last4) || empty($cardId) || empty($cardType)) {
            return false;
        }

        /* If already added */
        if ($this->_isAddedCard($customerId, $cardId, $cardType)) {
            return false;
        }

        try {
            $this->setCustomerId($customerId);
            $this->setCardId($cardId);
            $this->setCardNumber($last4);
            $this->setCardType($cardType);

            $this->save();
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('chargepayment')->__('Cannot save Customer Data.'));
        }

        return true;
    }

    /**
     * For check if already added
     *
     * @param $customerId
     * @param $cardId
     * @param $cardType
     * @return bool
     *
     * @version 20151026
     */
    protected function _isAddedCard($customerId, $cardId, $cardType) {
        $collection = $this->getCollection();

        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->addFieldToFilter('card_id', $cardId);
        $collection->addFieldToFilter('card_type', $cardType);

        return $collection->count() ? true : false;
    }

    /**
     * Return card list for customer
     *
     * @param $customerId
     * @return object
     *
     * @version 20151026
     */
    public function getCustomerCardList($customerId) {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('customer_id', $customerId);

        return $collection;
    }

    /**
     * For using in post form
     *
     * @param $entityId
     * @param $cardNumber
     * @param $cardType
     * @return string
     *
     * @version 50151027
     */
    public function getCardSecret($entityId, $cardNumber, $cardType) {
        return md5($entityId . '_' . $cardNumber . '_' . $cardType);
    }

    /**
     * Return Customer Saved Card if it exists
     *
     * @param $secretCard
     * @param $customerId
     * @return array
     *
     * @version 20151030
     */
    public function customerCardExists($secretCard, $customerId) {
        $result     = array();
        $collection = $this->getCustomerCardList($customerId);

        if (!$collection->count()) {
            return $result;
        }

        foreach($collection as $entity) {
            $secret = $this->getCardSecret($entity->getId(), $entity->getCardNumber(), $entity->getCardType());

            if ($secretCard === $secret) {
                $result = $entity;
                break;
            }
        }

        return $result;
    }

    /**
     * Remove Saved Card
     *
     * @param $entityId
     * @throws Mage_Core_Exception
     *
     * @version 20151030
     */
    public function removeCard($entityId) {
        if (empty($entityId)) {
            Mage::throwException(Mage::helper('chargepayment')->__('Unable to delete empty Card.'));
        }

        try {
            $this->load($entityId);
            $this->delete();
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('chargepayment')->__('Unable to delete Card.'));
        }
    }
}