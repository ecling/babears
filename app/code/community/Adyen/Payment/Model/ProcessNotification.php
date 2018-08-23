<?php

/**
 * Adyen Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Adyen
 * @package    Adyen_Payment
 * @copyright    Copyright (c) 2011 Adyen (http://www.adyen.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */
class Adyen_Payment_Model_ProcessNotification extends Mage_Core_Model_Abstract
{


    /**
     * Collected debug information
     *
     * @var array
     */
    protected $_debugData = array();

    /**
     * Hold the count so notifications can be logged separately in debugData
     *
     * @var int
     */
    protected $_count = 0;

    /**
     * Process the notification that is received by the Adyen platform
     * @param $response
     * @return string
     */
    public function processResponse($response)
    {
        // SOAP, JSON, HTTP POST
        $storeId = null;

        $this->_debugData['processResponse begin'] = 'Begin to process Notification';

        if (empty($response)) {
            $this->_debugData['error'] = 'Response is empty, please check your webserver that the result url accepts parameters';
            $this->_debug($storeId);
            return array('response' => '401');
        }

        // Log the results in log file and adyen_debug table
        $this->_debugData['response'] = $response;
        Mage::getResourceModel('adyen/adyen_debug')->assignData($response);

//      $params = new Varien_Object($response);
        // Create Varien_Object from response (soap compatible)
        $params = new Varien_Object();
        foreach ($response as $code => $value) {
            $params->setData($code, $value);
        }
        $actionName = $this->_getRequest()->getActionName();

        // authenticate result url
        $authStatus = Mage::getModel('adyen/authenticate')->authenticate($actionName, $params);
        if (!$authStatus['authentication']) {
            $this->_debugData['error'] = 'Autentication failure please check your notification username and password. This must be the same in Magento as in the Adyen platform';
            $this->_debug($storeId);
            return array('response' => '401', 'message' => $authStatus['message']);
        }

        // skip notification if notification is REPORT_AVAILABLE
        $eventCode = trim($params->getData('eventCode'));
        if ($eventCode == Adyen_Payment_Model_Event::ADYEN_EVENT_REPORT_AVAILABLE) {
            $this->_debugData['processResponse info'] = 'Skip notification REPORT_AVAILABLE';
            $this->_debug($storeId);
            return;
        }

        $this->_declareCommonVariables($params);
        $isInvalidKcp = $this->_isInvalidKcp($this->_paymentMethod, $this->_value);
        if ($isInvalidKcp) {
            $this->_debugData['processResponse info'] = 'Skip notification for KCP and 0 amount';
            $this->_debug($storeId);
            return;
        }

        // check if notification is not duplicate
        if (!$this->_isDuplicate($params)) {

            $incrementId = $params->getData('merchantReference');

            if ($incrementId) {
                $this->_debugData['info'] = 'Add this notification with Order increment_id to queue: ' . $incrementId;
                $this->_addNotificationToQueue($params);
            } else {
                $this->_debugData['error'] = 'Empty merchantReference';
            }
        } else {
            $this->_debugData['processResponse info'] = 'Skipping duplicate notification';
        }
        $this->_debug($storeId);
    }

    /*
     * Returns true if the payment method is KCP and the amount is 0
     */
    protected function _isInvalidKcp($paymentMethod, $amountValue)
    {
        if ($paymentMethod == Adyen_Payment_Model_Adyen_Hpp::KCP_CREDITCARD
            || $paymentMethod == Adyen_Payment_Model_Adyen_Hpp::KCP_BANKTRANSFER
        ) {
            if ($amountValue == 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if notification is already received
     * If this is the case ignore the notification
     * @param $params
     * @return bool
     */
    protected function _isDuplicate($params)
    {
        $pspReference = trim($params->getData('pspReference'));
        $success = trim($params->getData('success'));
        $eventCode = trim($params->getData('eventCode'));

        // if notification is already processed ignore it
        $isDuplicate = Mage::getModel('adyen/event')
            ->isDuplicate($pspReference, $eventCode, $success);
        if ($isDuplicate && $eventCode != Adyen_Payment_Model_Event::ADYEN_EVENT_RECURRING_CONTRACT) {
            return true;
        }
        return false;
    }

    // notification attributes
    protected $_pspReference;
    protected $_originalReference;
    protected $_merchantReference;
    protected $_eventCode;
    protected $_success;
    protected $_paymentMethod;
    protected $_reason;
    protected $_value;
    protected $_boletoOriginalAmount;
    protected $_boletoPaidAmount;
    protected $_modificationResult;
    protected $_klarnaReservationNumber;
    protected $_fraudManualReview;


    /**
     * @desc a public function for updateOrder to update a specific from the QueueController
     * @param $order
     * @param $params
     */
    public function updateOrder($order, $params)
    {
        $this->_debugData = array();

        $this->_debugData['processPosResponse begin'] = 'Begin to process this specific notification from the queue';

        $this->_debugData['params'] = $params;

        $this->_updateOrder($order, $params);

        $this->_debugData['processPosResponse end'] = 'end of process notification';

        return $this->_debugData;
    }

    /**
     * @param $order
     * @param $params
     */
    protected function _updateOrder($order, $params)
    {
        if (!($order->getPayment()->getMethodInstance() instanceof Adyen_Payment_Model_Adyen_Abstract)) {
            // This method only applies to Adyen orders
            return;
        }

        $this->_debugData[$this->_count]['_updateOrder'] = 'Updating the order';

        Mage::dispatchEvent('adyen_payment_process_notifications_before', array('order' => $order, 'adyen_response' => $params));
        if ($params->getData('handled')) {
            $this->_debug($order->getStoreId());
            return;
        }

        $this->_declareVariables($order, $params);

        $previousAdyenEventCode = $order->getAdyenEventCode();

        // add notification to comment history status is current status
        $this->_addStatusHistoryComment($order);

        // update order details
        $this->_updateAdyenAttributes($order, $params);

        // check if success is true of false or empty
        if (strcmp($this->_success, 'false') == 0 || strcmp($this->_success, '0') == 0 || strcmp($this->_success, '') == 0) {
            // Only cancel the order when it is in state pending, payment review or if the ORDER_CLOSED is failed (means split payment has not be successful)
            if ($order->getState() === Mage_Sales_Model_Order::STATE_PENDING_PAYMENT || $order->getState() === Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW || $this->_eventCode == Adyen_Payment_Model_Event::ADYEN_EVENT_ORDER_CLOSED) {
                $this->_debugData[$this->_count]['_updateOrder info'] = 'Going to cancel the order';

                // if payment is API check and if notification is an authorisation
                if ($this->_eventCode == Adyen_Payment_Model_Event::ADYEN_EVENT_AUTHORISATION && $this->_getPaymentMethodType($order) == 'api') {
                    // don't cancel the order becasue order was successfull through api
                    $this->_debugData[$this->_count]['_updateOrder warning'] = 'order is not cancelled because api result was succesfull';
                } else {
                    // don't cancel the order if previous state is authorisation with success=true
                    // Split payments can fail if the second payment has failed the first payment is refund/cancelled as well so if it is a split payment that failed cancel the order as well
                    if ($previousAdyenEventCode != "AUTHORISATION : TRUE" || $this->_eventCode == Adyen_Payment_Model_Event::ADYEN_EVENT_ORDER_CLOSED) {
                        $this->_holdCancelOrder($order, false);
                    } else {
                        $order->setAdyenEventCode($previousAdyenEventCode); // do not update the adyenEventCode
                        $this->_debugData[$this->_count]['_updateOrder warning'] = 'order is not cancelled because previous notification was a authorisation that succeeded';
                    }
                }
            } else {
                $this->_debugData[$this->_count]['_updateOrder info'] = 'Order is already processed so ignore this notification state is:' . $order->getState();
            }
        } else {
            // Notification is successful
            $this->_processNotification($order);
        }

        // update the order with status/adyen event and comment history
        $order->save();

        // save event for duplication
        $this->_storeNotification();

        Mage::dispatchEvent('adyen_payment_process_notifications_after', array('order' => $order, 'adyen_response' => $params));
    }

    protected function _declareCommonVariables($params)
    {
        //  declare the common parameters
        $this->_pspReference = trim($params->getData('pspReference'));
        $this->_originalReference = trim($params->getData('originalReference'));
        $this->_merchantReference = trim($params->getData('merchantReference'));
        $this->_eventCode = trim($params->getData('eventCode'));
        $this->_success = trim($params->getData('success'));
        $this->_paymentMethod = trim($params->getData('paymentMethod'));
        $this->_reason = trim($params->getData('reason'));

        $valueArray = $params->getData('amount');
        if ($valueArray && is_array($valueArray)) {
            $this->_value = isset($valueArray['value']) ? $valueArray['value'] : "";
        } elseif (is_object($valueArray)) {
            $this->_value = $valueArray->value; // for soap
        }
    }

    protected function _declareVariables($order, $params)
    {
        $this->_declareCommonVariables($params);

        // reset values because data can not be present in notification
        $this->_boletoOriginalAmount = null;
        $this->_boletoPaidAmount = null;
        $this->_fraudManualReview = false;
        $this->_modificationResult = null;
        $this->_klarnaReservationNumber = null;

        $additionalData = $params->getData('additionalData');

        // boleto data
        if ($this->_paymentMethodCode($order) == "adyen_boleto") {
            if ($additionalData && is_array($additionalData)) {
                $boletobancario = isset($additionalData['boletobancario']) ? $additionalData['boletobancario'] : null;
                if ($boletobancario && is_array($boletobancario)) {
                    $this->_boletoOriginalAmount = isset($boletobancario['originalAmount']) ? trim($boletobancario['originalAmount']) : "";
                    $this->_boletoPaidAmount = isset($boletobancario['paidAmount']) ? trim($boletobancario['paidAmount']) : "";
                }
            }
        }

        if ($additionalData && is_array($additionalData)) {

            // check if the payment is in status manual review
            $fraudManualReview = isset($additionalData['fraudManualReview']) ? $additionalData['fraudManualReview'] : "";
            if ($fraudManualReview == "true") {
                $this->_fraudManualReview = true;
            } else {
                $this->_fraudManualReview = false;
            }

            // modification.action is it for JSON
            $modificationActionJson = isset($additionalData['modification.action']) ? $additionalData['modification.action'] : null;
            if ($modificationActionJson != "") {
                $this->_modificationResult = $modificationActionJson;
            }

            // HTTP POST and SOAP have this in a array
            $modification = isset($additionalData['modification']) ? $additionalData['modification'] : null;
            if ($modification && is_array($modification)) {
                $this->_modificationResult = isset($modification['action']) ? trim($modification['action']) : "";
            }
            $additionalData2 = isset($additionalData['additionalData']) ? $additionalData['additionalData'] : null;
            if ($additionalData2 && is_array($additionalData2)) {
                $this->_klarnaReservationNumber = isset($additionalData2['acquirerReference']) ? trim($additionalData2['acquirerReference']) : "";
            }
        }
    }

    /**
     * @param $order
     * @param $params
     */
    protected function _updateAdyenAttributes($order, $params)
    {
        $this->_debugData[$this->_count]['_updateAdyenAttributes'] = 'Updating the Adyen attributes of the order';

        $additionalData = $params->getData('additionalData');
        $paymentObj = $order->getPayment();
        $_paymentCode = $this->_paymentMethodCode($order);

        if ($this->_eventCode == Adyen_Payment_Model_Event::ADYEN_EVENT_AUTHORISATION
            || $this->_eventCode == Adyen_Payment_Model_Event::ADYEN_EVENT_HANDLED_EXTERNALLY
            || ($this->_eventCode == Adyen_Payment_Model_Event::ADYEN_EVENT_CAPTURE && $_paymentCode == "adyen_pos")
        ) {

            $paymentObj->setLastTransId($this->_merchantReference)
                ->setCcType($this->_paymentMethod);

            // if current notification is authorisation : false and the  previous notification was authorisation : true do not update pspreference
            if (strcmp($this->_success, 'false') == 0 || strcmp($this->_success, '0') == 0 || strcmp($this->_success, '') == 0) {
                $previousAdyenEventCode = $order->getAdyenEventCode();
                if ($previousAdyenEventCode != "AUTHORISATION : TRUE") {
                    $this->_updateOrderPaymentWithAdyenAttributes($paymentObj, $additionalData);
                }
            } else {
                $this->_updateOrderPaymentWithAdyenAttributes($paymentObj, $additionalData);
            }
        }
    }

    protected function _updateOrderPaymentWithAdyenAttributes($paymentObj, $additionalData)
    {
        if ($additionalData && is_array($additionalData)) {
            $avsResult = (isset($additionalData['avsResult'])) ? $additionalData['avsResult'] : "";
            $cvcResult = (isset($additionalData['cvcResult'])) ? $additionalData['cvcResult'] : "";
            $totalFraudScore = (isset($additionalData['totalFraudScore'])) ? $additionalData['totalFraudScore'] : "";
            $ccLast4 = (isset($additionalData['cardSummary'])) ? $additionalData['cardSummary'] : "";
            $refusalReasonRaw = (isset($additionalData['refusalReasonRaw'])) ? $additionalData['refusalReasonRaw'] : "";
            $acquirerReference = (isset($additionalData['acquirerReference'])) ? $additionalData['acquirerReference'] : "";
            $authCode = (isset($additionalData['authCode'])) ? $additionalData['authCode'] : "";
            $cardBin = (isset($additionalData['cardBin'])) ? $additionalData['cardBin'] : "";
        }

        // if there is no server communication setup try to get last4 digits from reason field
        if (!isset($ccLast4) || $ccLast4 == "") {
            $ccLast4 = $this->_retrieveLast4DigitsFromReason($this->_reason);
        }

        $paymentObj->setAdyenPspReference($this->_pspReference);

        if ($this->_klarnaReservationNumber != "") {
            $paymentObj->setAdyenKlarnaNumber($this->_klarnaReservationNumber);
        }
        if (isset($ccLast4) && $ccLast4 != "") {
            $paymentObj->setccLast4($ccLast4);
        }
        if (isset($avsResult) && $avsResult != "") {
            $paymentObj->setAdyenAvsResult($avsResult);
        }
        if (isset($cvcResult) && $cvcResult != "") {
            $paymentObj->setAdyenCvcResult($cvcResult);
        }
        if ($this->_boletoPaidAmount != "") {
            $paymentObj->setAdyenBoletoPaidAmount($this->_boletoPaidAmount);
        }
        if (isset($totalFraudScore) && $totalFraudScore != "") {
            $paymentObj->setAdyenTotalFraudScore($totalFraudScore);
        }
        if (isset($refusalReasonRaw) && $refusalReasonRaw != "") {
            $paymentObj->setAdyenRefusalReasonRaw($refusalReasonRaw);
        }
        if (isset($acquirerReference) && $acquirerReference != "") {
            $paymentObj->setAdyenAcquirerReference($acquirerReference);
        }
        if (isset($authCode) && $authCode != "") {
            $paymentObj->setAdyenAuthCode($authCode);
        }
        if (isset($cardBin) && $cardBin != "") {
            $paymentObj->setAdyenCardBin($cardBin);
        }
    }

    /**
     * retrieve last 4 digits of card from the reason field
     * @param $reason
     * @return string
     */
    protected function _retrieveLast4DigitsFromReason($reason)
    {
        $result = "";

        if ($reason != "") {
            $reasonArray = explode(":", $reason);
            if ($reasonArray != null && is_array($reasonArray)) {
                if (isset($reasonArray[1])) {
                    $result = $reasonArray[1];
                }
            }
        }
        return $result;
    }

    /**
     * @param $order
     * @param $params
     */
    protected function _storeNotification()
    {
        $success = ($this->_success == "true" || $this->_success == "1") ? true : false;

        try {
            //save all response data for a pure duplicate detection
            Mage::getModel('adyen/event')
                ->setPspReference($this->_pspReference)
                ->setAdyenEventCode($this->_eventCode)
                ->setAdyenEventResult($this->_eventCode)
                ->setIncrementId($this->_merchantReference)
                ->setPaymentMethod($this->_paymentMethod)
                ->setCreatedAt(now())
                ->setSuccess($success)
                ->saveData();

            $this->_debugData[$this->_count]['_storeNotification'] = 'Notification is saved in adyen_event_data table';
        } catch (Exception $e) {
            $this->_debugData[$this->_count]['_storeNotification error'] = 'Notification could not be saved in adyen_event_data table error message is: ' . $e->getMessage();
            Mage::logException($e);
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $params
     */
    protected function _processNotification($order)
    {
        $this->_debugData[$this->_count]['_processNotification'] = 'Processing the notification';
        $_paymentCode = $this->_paymentMethodCode($order);

        switch ($this->_eventCode) {
            case Adyen_Payment_Model_Event::ADYEN_EVENT_REFUND_FAILED:
                // do nothing only inform the merchant with order comment history
                break;
            case Adyen_Payment_Model_Event::ADYEN_EVENT_REFUND:
                $ignoreRefundNotification = $this->_getConfigData('ignore_refund_notification', 'adyen_abstract', $order->getStoreId());
                if ($ignoreRefundNotification != true) {
                    $this->_refundOrder($order);
                    //refund completed
                    $this->_setRefundAuthorized($order);
                } else {
                    $this->_debugData[$this->_count]['_processNotification info'] = 'Setting to ignore refund notification is enabled so ignore this notification';
                }
                break;
            case Adyen_Payment_Model_Event::ADYEN_EVENT_PENDING:
                if ($this->_getConfigData('send_email_bank_sepa_on_pending', 'adyen_abstract', $order->getStoreId())) {
                    // Check if payment is banktransfer or sepa if true then send out order confirmation email
                    $isBankTransfer = $this->_isBankTransfer($this->_paymentMethod);
                    if ($isBankTransfer || $this->_paymentMethod == 'sepadirectdebit') {
                        $order->sendNewOrderEmail(); // send order email
                        $this->_debugData[$this->_count]['_processNotification send email'] = 'Send orderconfirmation email to shopper';
                    }
                }
                break;
            case Adyen_Payment_Model_Event::ADYEN_EVENT_HANDLED_EXTERNALLY:
            case Adyen_Payment_Model_Event::ADYEN_EVENT_AUTHORISATION:
                $this->_authorizePayment($order, $this->_paymentMethod);
                break;
            case Adyen_Payment_Model_Event::ADYEN_EVENT_MANUAL_REVIEW_REJECT:
                // don't do anything it will send a CANCEL_OR_REFUND notification when this payment is cancelled
                break;
            case Adyen_Payment_Model_Event::ADYEN_EVENT_MANUAL_REVIEW_ACCEPT:
                // only process this if you are on auto capture. On manual capture you will always get Capture or CancelOrRefund notification
                if ($this->_isAutoCapture($order)) {
                    $this->_setPaymentAuthorized($order, false);
                }
                break;
            case Adyen_Payment_Model_Event::ADYEN_EVENT_CAPTURE:
                if ($_paymentCode != "adyen_pos") {
                    // ignore capture if you are on auto capture (this could be called if manual review is enabled and you have a capture delay)
                    if (!$this->_isAutoCapture($order)) {
                        $this->_setPaymentAuthorized($order, false, true, true);
                    }
                } else {
                    // do nothing (this is a backwards-compatible notification that can be ignored
                    $this->_debugData[$this->_count]['_processNotification info'] = 'Ignore this refund already done processing on AUTHORISATION';
                }
                break;
            case Adyen_Payment_Model_Event::ADYEN_EVENT_OFFER_CLOSED:
                if (!$order->canCancel()) {
                    // Move the order from PAYMENT_REVIEW to NEW, so that can be cancelled
                    $order->setState(Mage_Sales_Model_Order::STATE_NEW);
                }
                $this->_holdCancelOrder($order, true);
                break;
            case Adyen_Payment_Model_Event::ADYEN_EVENT_CAPTURE_FAILED:
            case Adyen_Payment_Model_Event::ADYEN_EVENT_CANCELLATION:
            case Adyen_Payment_Model_Event::ADYEN_EVENT_CANCELLED:
                $this->_holdCancelOrder($order, true);
                break;
            case Adyen_Payment_Model_Event::ADYEN_EVENT_CANCEL_OR_REFUND:
                if (isset($this->_modificationResult) && $this->_modificationResult != "") {
                    if ($this->_modificationResult == "cancel") {
                        $this->_holdCancelOrder($order, true);
                    } elseif ($this->_modificationResult == "refund") {
                        $this->_refundOrder($order);
                        //refund completed
                        $this->_setRefundAuthorized($order);
                    }
                } else {
                    if ($order->isCanceled() || $order->getState() === Mage_Sales_Model_Order::STATE_HOLDED) {
                        $this->_debugData[$this->_count]['_processNotification info'] = 'Order is already cancelled or holded so do nothing';
                    } else if ($order->canCancel() || $order->canHold()) {
                        $this->_debugData[$this->_count]['_processNotification info'] = 'try to cancel the order';
                        $this->_holdCancelOrder($order, true);
                    } else {
                        $this->_debugData[$this->_count]['_processNotification info'] = 'try to refund the order';
                        // refund
                        $this->_refundOrder($order);
                        //refund completed
                        $this->_setRefundAuthorized($order);
                    }
                }
                break;
            case Adyen_Payment_Model_Event::ADYEN_EVENT_RECURRING_CONTRACT:

                $this->_debugData[$this->_count]['process recurring contract start'] = 'Processing Recurring Contract notification';

                // get payment object
                $payment = $order->getPayment();

                // storedReferenceCode
                $recurringDetailReference = $this->_pspReference;

                // check if there is already a BillingAgreement
                $agreement = Mage::getModel('adyen/billing_agreement')->load($recurringDetailReference, 'reference_id');

                if ($agreement && $agreement->getAgreementId() > 0 && $agreement->isValid()) {

                    $this->_debugData[$this->_count]['process recurring contract exists'] = 'Billing agreement for recurring contract already exists so update it';

                    $agreement->addOrderRelation($order);
                    $agreement->setStatus($agreement::STATUS_ACTIVE);
                    $agreement->setIsObjectChanged(true);
                    $order->addRelatedObject($agreement);
                    $message = Mage::helper('adyen')->__('Used existing billing agreement #%s.', $agreement->getReferenceId());

                } else {

                    $this->_debugData[$this->_count]['process recurring contract new'] = 'Create a new billing agreement for this recurring contract';

                    // set billing agreement data
                    $payment->setBillingAgreementData(array(
                        'billing_agreement_id' => $recurringDetailReference,
                        'method_code' => $_paymentCode
                    ));

                    // create billing agreement for this order
                    $agreement = Mage::getModel('adyen/billing_agreement');
                    $agreement->setStoreId($order->getStoreId());
                    $agreement->importOrderPayment($payment);

                    $customerReference = $agreement->getCustomerReference();

                    if ($customerReference) {

                        $this->_debugData[$this->_count]['process recurring contract customerref'] = 'There is a custumor reference';

                        $listRecurringContracts = Mage::getSingleton('adyen/api')->listRecurringContracts($agreement->getCustomerReference(), $agreement->getStoreId());

                        $contractDetail = null;
                        // get currenct Contract details and get list of all current ones
                        $recurringReferencesList = array();
                        foreach ($listRecurringContracts as $rc) {
                            $recurringReferencesList[] = $rc['recurringDetailReference'];
                            if (isset($rc['recurringDetailReference']) && $rc['recurringDetailReference'] == $recurringDetailReference) {
                                $contractDetail = $rc;
                            }
                        }

                        if ($contractDetail != null) {

                            $this->_debugData[$this->_count]['process recurring contract contractdetail'] = 'There is a contractDetail result';

                            // update status of the agreements in magento
                            $billingAgreements = Mage::getResourceModel('adyen/billing_agreement_collection')
                                ->addFieldToFilter('customer_id', $agreement->getCustomerReference());

                            foreach ($billingAgreements as $billingAgreement) {
                                if (!in_array($billingAgreement->getReferenceId(), $recurringReferencesList)) {
                                    $billingAgreement->setStatus(Adyen_Payment_Model_Billing_Agreement::STATUS_CANCELED);
                                    $billingAgreement->save();
                                } else {
                                    $billingAgreement->setStatus(Adyen_Payment_Model_Billing_Agreement::STATUS_ACTIVE);
                                    $billingAgreement->save();
                                }
                            }

                            $this->_debugData[$this->_count]['process recurring contract existing updated'] = 'The existing billing agreements are updated';

                            $agreement->parseRecurringContractData($contractDetail);

                            if ($agreement->isValid()) {

                                $this->_debugData[$this->_count]['process recurring contract billing agreement'] = 'The billing agreements is valid';
                                $message = Mage::helper('adyen')->__('Created billing agreement #%s.', $agreement->getReferenceId());

                                // save into sales_billing_agreement_order
                                $agreement->addOrderRelation($order);

                                // add to order to save agreement
                                $order->addRelatedObject($agreement);
                            } else {
                                $this->_debugData[$this->_count]['process recurring contract billing agreement'] = 'The billing agreements is not valid';
                                $message = Mage::helper('adyen')->__('Failed to create billing agreement for this order.');
                            }
                        } else {
                            $this->_debugData[$this->_count]['_processNotification error'] = 'Failed to create billing agreement for this order (listRecurringCall did not contain contract)';
                            $this->_debugData[$this->_count]['_processNotification ref'] = sprintf('recurringDetailReference in notification is %s', $recurringDetailReference);
                            $this->_debugData[$this->_count]['_processNotification customer ref'] = sprintf('CustomerReference is: %s and storeId is %s', $agreement->getCustomerReference(), $agreement->getStoreId());
                            $this->_debugData[$this->_count]['_processNotification customer result'] = $listRecurringContracts;
                            $message = Mage::helper('adyen')->__('Failed to create billing agreement for this order (listRecurringCall did not contain contract)');
                        }
                    } else {
                        $this->_debugData[$this->_count]['_processNotification error'] = 'merchantReference is empty, probably checked out as quest we can\'t save billing agreemnents for quest checkout';
                    }
                }

                if ($message) {
                    $comment = $order->addStatusHistoryComment($message);
                    $order->addRelatedObject($comment);
                }
                break;
            default:
                $this->_debugData[$this->_count]['_processNotification info'] = sprintf('This notification event: %s is not supported so will be ignored', $this->_eventCode);
                break;
        }
    }

    /**
     * @desc Revert back to NEW status if previous notification has cancelled the order
     * @param $order
     */
    protected function _uncancelOrder($order)
    {

        if ($order->isCanceled() && $this->_getConfigData('uncancelorder', 'adyen_abstract')) {

            $this->_debugData[$this->_count]['_uncancelOrder'] = 'Uncancel the order because could be that it is cancelled in a previous notification';

            $orderStatus = $this->_getConfigData('order_status', 'adyen_abstract', $order->getStoreId());

            $order->setState(Mage_Sales_Model_Order::STATE_NEW);
            $order->setStatus($orderStatus);
            $order->setBaseDiscountCanceled(0);
            $order->setBaseShippingCanceled(0);
            $order->setBaseSubtotalCanceled(0);
            $order->setBaseTaxCanceled(0);
            $order->setBaseTotalCanceled(0);
            $order->setDiscountCanceled(0);
            $order->setShippingCanceled(0);
            $order->setSubtotalCanceled(0);
            $order->setTaxCanceled(0);
            $order->setTotalCanceled(0);
            $order->save();

            try {
                foreach ($order->getAllItems() as $item) {
                    $item->setQtyCanceled(0);
                    $item->setTaxCanceled(0);
                    $item->setHiddenTaxCanceled(0);
                    $item->save();
                }
            } catch (Exception $e) {
                $this->_debugData[$this->_count]['_uncancelOrder'] = 'Failed to cancel orderlines exception: ' . $e->getMessage();

            }
        }
    }

    /**
     * @param $order
     * @return bool
     */
    protected function _refundOrder($order)
    {
        $this->_debugData[$this->_count]['_refundOrder'] = 'Refunding the order';

        $currency = $order->getOrderCurrencyCode(); // use orderCurrency because adyen respond in the same currency as in the request

        // check if it is a split payment if so save the refunded data
        if ($this->_originalReference != "") {
            $orderPayment = Mage::getModel('adyen/order_payment')
                ->getCollection()
                ->addFieldToFilter('pspreference', $this->_originalReference)
                ->getFirstItem();

            if ($orderPayment->getId() > 0) {
                $currency = $order->getOrderCurrencyCode();
                $amountRefunded = $orderPayment->getTotalRefunded() +
                    Mage::helper('adyen')->originalAmount($this->_value, $currency);
                $orderPayment->setTotalRefunded($amountRefunded);
                $orderPayment->save();
            }
        }

        // Don't create a credit memo if refund is initialize in Magento because in this case the credit memo already exists
        $result = Mage::getModel('adyen/event')
            ->getEvent($this->_pspReference, '[refund-received]');
        if (!empty($result)) {
            $this->_debugData[$this->_count]['_refundOrder ignore'] = 'Skip refund process because credit memo is already created';
            return false;
        }

        $_mail = (bool)$this->_getConfigData('send_update_mail', 'adyen_abstract', $order->getStoreId());
        $amount = Mage::helper('adyen')->originalAmount($this->_value, $currency);

        if ($order->canCreditmemo()) {
            $service = Mage::getModel('sales/service_order', $order);
            $creditmemo = $service->prepareCreditmemo();
            $creditmemo->getOrder()->setIsInProcess(true);

            //set refund data on the order
            $creditmemo->setGrandTotal($amount);
            $creditmemo->setBaseGrandTotal($amount);
            $creditmemo->save();

            try {
                Mage::getModel('core/resource_transaction')
                    ->addObject($creditmemo)
                    ->addObject($creditmemo->getOrder())
                    ->save();
                //refund
                $creditmemo->refund();
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($creditmemo)
                    ->addObject($creditmemo->getOrder());
                if ($creditmemo->getInvoice()) {
                    $transactionSave->addObject($creditmemo->getInvoice());
                }
                $transactionSave->save();
                if ($_mail) {
                    $creditmemo->getOrder()->setCustomerNoteNotify(true);
                    $creditmemo->sendEmail();
                }
                $this->_debugData[$this->_count]['_refundOrder done'] = 'Credit memo is created';
            } catch (Exception $e) {
                $this->_debugData[$this->_count]['_refundOrder error'] = 'Error creating credit memo error message is: ' . $e->getMessage();
                Mage::logException($e);
            }
        } else {
            $this->_debugData[$this->_count]['_refundOrder error'] = 'Order can not be refunded';
        }
    }

    /**
     * @param $order
     */
    protected function _setRefundAuthorized($order)
    {
        $this->_debugData[$this->_count]['_setRefundAuthorized'] = 'Status update to default status or refund_authorized status if this is set';


        // check if it is a full or partial refund
        $amount = $this->_value;
        $currency = $order->getOrderCurrencyCode();
        $orderAmount = (int)Mage::helper('adyen')->formatAmount($order->getGrandTotal(), $currency);

        if ($amount == $orderAmount) {
            $status = $this->_getConfigData('refund_authorized', 'adyen_abstract', $order->getStoreId());
            $this->_debugData[$this->_count]['_setRefundAuthorized full'] = 'This is a full refund. Status selected is:' . $status;
            /*
             * When a credit memo is generated and contains the full refund,
             * in Magento the State should be on CLOSED, the setData is the only way to force the State to be closed
             */
            $order->setData('state', Mage_Sales_Model_Order::STATE_CLOSED);
        } else {
            $status = $this->_getConfigData('refund_partial_authorized', 'adyen_abstract', $order->getStoreId());
            $this->_debugData[$this->_count]['_setRefundAuthorized partial'] = 'This is a partial refund. Status selected is:' . $status;
        }

        // if no status is selected don't change the status and use current status
        $status = (!empty($status)) ? $status : $order->getStatus();
        $order->addStatusHistoryComment(Mage::helper('adyen')->__('Adyen Refund Successfully completed'), $status);
        $order->sendOrderUpdateEmail((bool)$this->_getConfigData('send_update_mail', 'adyen_abstract', $order->getStoreId()));
        /**
         * save the order this is needed for older magento version so that status is not reverted to state NEW
         */
        $order->save();
    }

    /**
     * @param $order
     * @param $payment_method
     */
    protected function _authorizePayment($order, $payment_method)
    {
        $this->_debugData[$this->_count]['_authorizePayment'] = 'Authorisation of the order';

        $this->_uncancelOrder($order);

        $fraudManualReviewStatus = $this->_getFraudManualReviewStatus($order);

        // If manual review is active and a seperate status is used then ignore the pre authorized status
        if ($this->_fraudManualReview != true || $fraudManualReviewStatus == "") {
            $this->_setPrePaymentAuthorized($order);
        } else {
            $this->_debugData[$this->_count]['_authorizePayment info'] = 'Ignore the pre authorized status because the order is under manual review and use the Manual review status';
        }

        $this->_prepareInvoice($order);

        $_paymentCode = $this->_paymentMethodCode($order);

        // for boleto and multibanco confirmation mail is send on order creation
        if (!in_array($payment_method, array('adyen_boleto', 'adyen_multibanco'))) {
            // send order confirmation mail after invoice creation so merchant can add invoicePDF to this mail
            $order->sendNewOrderEmail(); // send order email
        }

        if (($payment_method == "c_cash" && $this->_getConfigData('create_shipment', 'adyen_cash', $order->getStoreId())) || ($this->_getConfigData('create_shipment', 'adyen_pos', $order->getStoreId()) && $_paymentCode == "adyen_pos")) {
            $this->_createShipment($order);
        }
    }

    /**
     * @param $order
     */
    private function _setPrePaymentAuthorized($order)
    {
        $status = $this->_getConfigData('payment_pre_authorized', 'adyen_abstract', $order->getStoreId());

        // only do this if status in configuration is set
        if (!empty($status)) {

            $statusObject = Mage::getModel('sales/order_status')->getCollection()
                ->addFieldToFilter('main_table.status', $status)
                ->addFieldToFilter('state_table.is_default', true)
                ->joinStates()
                ->getFirstItem();
            $state = $statusObject->getState();
            $order->setState($state, $status, Mage::helper('adyen')->__('Payment is pre authorised waiting for capture'));

            /**
             * save the order this is needed for older magento version so that status is not reverted to state NEW
             */
            $order->save();
            $order->sendOrderUpdateEmail((bool)$this->_getConfigData('send_update_mail', 'adyen_abstract', $order->getStoreId()));
            $this->_debugData[$this->_count]['_setPrePaymentAuthorized'] = 'Order status is changed to Pre-authorised status, status is ' . $status;
        } else {
            $this->_debugData[$this->_count]['_setPrePaymentAuthorized'] = 'No pre-authorised status is used so ignore';
        }
    }

    /**
     * @param $order
     */
    protected function _prepareInvoice($order)
    {
        $this->_debugData[$this->_count]['_prepareInvoice'] = 'Prepare invoice for order';
        $payment = $order->getPayment()->getMethodInstance();

        $_mail = (bool)$this->_getConfigData('send_update_mail', 'adyen_abstract', $order->getStoreId());

        //capture mode
        if (!$this->_isAutoCapture($order)) {
            $order->addStatusHistoryComment(Mage::helper('adyen')->__('Capture Mode set to Manual'));
            $order->sendOrderUpdateEmail($_mail);
            $this->_debugData[$this->_count]['_prepareInvoice capture mode'] = 'Capture mode is set to Manual';

            // show message if order is in manual review
            if ($this->_fraudManualReview) {
                // check if different status is selected
                $fraudManualReviewStatus = $this->_getFraudManualReviewStatus($order);
                if ($fraudManualReviewStatus != "") {
                    $status = $fraudManualReviewStatus;
                    $comment = "Adyen Payment is in Manual Review check the Adyen platform";
                    $order->addStatusHistoryComment(Mage::helper('adyen')->__($comment), $status);
                    /**
                     * save the order this is needed for older magento version so that status is not reverted to state NEW
                     */
                    $order->save();
                }
            }

            $createPendingInvoice = (bool)$this->_getConfigData('create_pending_invoice', 'adyen_abstract', $order->getStoreId());
            if (!$createPendingInvoice) {
                $this->_debugData[$this->_count]['_prepareInvoice done'] = 'Setting pending invoice is off so don\'t create an invoice wait for the capture notification';
                return;
            }
        }

        // Check if this is the first partial authorisation or if there is already been an authorisation
        $paymentObj = $order->getPayment();
        $orderCurrencyCode = $order->getOrderCurrencyCode();

        // save into adyen_order_payment
        $amount = Mage::helper('adyen')->originalAmount($this->_value, $orderCurrencyCode);
        Mage::getModel('adyen/order_payment')
            ->setPspreference($this->_pspReference)
            ->setMerchantReference($this->_merchantReference)
            ->setPaymentId($paymentObj->getId())
            ->setPaymentMethod($this->_paymentMethod)
            ->setAmount($amount)
            ->setTotalRefunded(0)
            ->save();


        // validate if amount is total amount

        $orderAmount = (int)Mage::helper('adyen')->formatAmount($order->getGrandTotal(), $orderCurrencyCode);

        if ($this->_isTotalAmount($orderAmount)) {
            $this->_createInvoice($order);
        } else {
            $this->_debugData[$this->_count]['_prepareInvoice partial authorisation step1'] = 'This is a partial AUTHORISATION';
            $authorisationAmount = $paymentObj->getAdyenAuthorisationAmount();
            if ($authorisationAmount != "") {
                $this->_debugData[$this->_count]['_prepareInvoice partial authorisation step2'] = 'There is already a partial AUTHORISATION received check if this combined with the previous amounts match the total amount of the order';
                $authorisationAmount = (int)$authorisationAmount;
                $currentValue = (int)$this->_value;
                $totalAuthorisationAmount = $authorisationAmount + $currentValue;

                // update amount in column
                $paymentObj->setAdyenAuthorisationAmount($totalAuthorisationAmount);

                if ($totalAuthorisationAmount == $orderAmount) {
                    $this->_debugData[$this->_count]['_prepareInvoice partial authorisation step3'] = 'The full amount is paid. This is the latest AUTHORISATION notification. Create the invoice';
                    $this->_createInvoice($order);
                } else {
                    // this can be multiple times so use envenData as unique key
                    $this->_debugData[$this->_count]['_prepareInvoice partial authorisation step3'] = 'The full amount is not reached. Wait for the next AUTHORISATION notification. The current amount that is authorized is:' . $totalAuthorisationAmount;
                }
            } else {
                $this->_debugData[$this->_count]['_prepareInvoice partial authorisation step2'] = 'This is the first partial AUTHORISATION save this into the adyen_authorisation_amount field';
                $paymentObj->setAdyenAuthorisationAmount($this->_value);
            }
        }

        $order->sendOrderUpdateEmail($_mail);
    }


    protected function _getFraudManualReviewStatus($order)
    {
        return $this->_getConfigData('fraud_manual_review_status', 'adyen_abstract', $order->getStoreId());
    }

    protected function _getFraudManualReviewAcceptStatus($order)
    {
        return $this->_getConfigData('fraud_manual_review_accept_status', 'adyen_abstract', $order->getStoreId());
    }

    protected function _isTotalAmount($orderAmount)
    {
        $this->_debugData[$this->_count]['_isTotalAmount'] = 'Validate if AUTHORISATION notification has the total amount of the order';
        $value = (int)$this->_value;

        if ($value >= $orderAmount) {
            $this->_debugData[$this->_count]['_isTotalAmount result'] = 'AUTHORISATION has the full amount';
            return true;
        } else {
            $this->_debugData[$this->_count]['_isTotalAmount result'] = 'This is a partial AUTHORISATION, the amount is ' . $this->_value;
            return false;
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    protected function _createInvoice($order)
    {
        $this->_debugData[$this->_count]['_createInvoice'] = 'Creating invoice for order';

        // Set order state to new because with order state payment_review it is not possible to create an invoice
        if (strcmp($order->getState(), Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) == 0) {
            $order->setState(Mage_Sales_Model_Order::STATE_NEW);
        }

        // Check to see if the order is in the "Hold" state, and unhold when it is.
        if ($order->canUnhold() && $this->_getConfigData('unholdorder', 'adyen_abstract')) {
            $order->unhold();
            $order->save();
        }

        if ($order->canInvoice()) {

            /* We do not use this inside a transaction because order->save() is always done on the end of the notification
             * and it could result in a deadlock see https://github.com/Adyen/magento/issues/334
             */
            try {
                $invoice = $order->prepareInvoice();
                $invoice->getOrder()->setIsInProcess(true);

                // set transaction id so you can do a online refund from credit memo
                $invoice->setTransactionId($this->_pspReference);

                $autoCapture = $this->_isAutoCapture($order);
                $createPendingInvoice = (bool)$this->_getConfigData('create_pending_invoice', 'adyen_abstract', $order->getStoreId());

                if ((!$autoCapture) && ($createPendingInvoice)) {

                    // if amount is zero create a offline invoice
                    $value = (int)$this->_value;
                    if ($value == 0) {
                        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                    } else {
                        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::NOT_CAPTURE);
                    }

                    $invoice->register();
                } else {
                    $invoice->register()->pay();
                }


                // set the state to pending because otherwise magento will automatically set it to processing when you save the order
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);

                /*
                 * Save the order otherwise in old magento versions our status is not updated the
                 * processing status that it gets here because the invoice is created.
                 */
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());

                $transactionSave->save();

                $this->_debugData[$this->_count]['_createInvoice done'] = 'Created invoice status is: ' . $order->getStatus() . ' state is:' . $order->getState();
            } catch (Exception $e) {
                $this->_debugData[$this->_count]['_createInvoice error'] = 'Error saving invoice. The error message is: ' . $e->getMessage();
                Mage::logException($e);
            }

            $this->_setPaymentAuthorized($order);

            $invoiceAutoMail = (bool)$this->_getConfigData('send_invoice_update_mail', 'adyen_abstract', $order->getStoreId());
            if ($invoiceAutoMail) {
                $invoice->sendEmail();
            }
        } else {
            $this->_debugData[$this->_count]['_createInvoice error'] = 'It is not possible to create invoice for this order';

            // TODO: check if pending invoice exists if so capture this invoice
        }
    }

    /**
     * @param $order
     * @return bool
     */
    protected function _isAutoCapture($order)
    {
        // validate if payment methods allowes manual capture
        if ($this->_manualCaptureAllowed()) {
            $captureMode = trim($this->_getConfigData('capture_mode', 'adyen_abstract', $order->getStoreId()));
            $sepaFlow = trim($this->_getConfigData('flow', 'adyen_sepa', $order->getStoreId()));
            $_paymentCode = $this->_paymentMethodCode($order);
            $captureModeOpenInvoice = $this->_getConfigData('auto_capture_openinvoice', 'adyen_abstract', $order->getStoreId());
            $captureModePayPal = trim($this->_getConfigData('paypal_capture_mode', 'adyen_abstract', $order->getStoreId()));

            //check if it is a banktransfer. Banktransfer only a Authorize notification is send.
            $isBankTransfer = $this->_isBankTransfer($this->_paymentMethod);

            /**
             * Payment method IDeal, Cash, adyen_pos and adyen_boleto are always auto capture
             * For sepadirectdebit in sale modues is always auto capture but in auth/cap modus it will follow the overall capture modus
             */
            if (strcmp($this->_paymentMethod, 'ideal') === 0 ||
                strcmp($this->_paymentMethod, 'c_cash') === 0 ||
                $_paymentCode == "adyen_pos" ||
                $isBankTransfer == true ||
                (($_paymentCode == "adyen_sepa" || ($_paymentCode == "adyen_oneclick" && strcmp($this->_paymentMethod, 'sepadirectdebit') === 0)) && $sepaFlow != "authcap") ||
                $_paymentCode == "adyen_boleto" || $_paymentCode == "adyen_multibanco"
            ) {
                $this->_debugData[$this->_count]['_isAutoCapture result'] = 'openinvoice capture mode is set to auto capture because payment method does not allow manual capture';
                return true;
            }
            // if auto capture mode for openinvoice is turned on then use auto capture
            if ($captureModeOpenInvoice == true && Mage::helper('adyen')->isOpenInvoice($this->_paymentMethod)) {
                $this->_debugData[$this->_count]['_isAutoCapture result'] = 'openinvoice capture mode is set to auto capture';
                return true;
            }

            // by default openinvoice payment methods are manual capture
            if (Mage::helper('adyen')->isOpenInvoice($this->_paymentMethod)) {
                return false;
            }

            // if PayPal capture modues is different from the default use this one
            if (strcmp($this->_paymentMethod, 'paypal') === 0 && $captureModePayPal != "") {
                if (strcmp($captureModePayPal, 'auto') === 0) {
                    $this->_debugData[$this->_count]['_isAutoCapture result'] = 'Paypal capture mode is set to auto capture';
                    return true;
                } elseif (strcmp($captureModePayPal, 'manual') === 0) {
                    $this->_debugData[$this->_count]['_isAutoCapture result'] = 'Paypal capture mode is set to manual capture';
                    return false;
                }
            }
            if (strcmp($captureMode, 'manual') === 0) {
                $this->_debugData[$this->_count]['_isAutoCapture result'] = 'Fall back on default capture delay that is manual capture';
                return false;
            }

            $this->_debugData[$this->_count]['_isAutoCapture result'] = 'Fall back on default capture delay that is immediate capture';
            return true;
        } else {
            $this->_debugData[$this->_count]['_isAutoCapture result'] = 'This payment method does not allow manual capture';
            return true;
        }
    }

    /**
     * Validate if this payment methods allows manual capture
     * This is a default can be forced differently to overrule on acquirer level
     *
     * @return bool|null
     */
    protected function _manualCaptureAllowed()
    {
        $manualCaptureAllowed = null;
        $paymentMethod = $this->_paymentMethod;

        // For all openinvoice methods is manual capture allowed
        if (Mage::helper('adyen')->isOpenInvoice($paymentMethod)) {
            return true;
        }

        switch ($paymentMethod) {
            case 'cup':
            case 'cartebancaire':
            case 'visa':
            case 'visadankort':
            case 'mc':
            case 'uatp':
            case 'amex':
            case 'maestro':
            case 'maestrouk':
            case 'diners':
            case 'discover':
            case 'jcb':
            case 'laser':
            case 'paypal':
            case 'sepadirectdebit':
                $manualCaptureAllowed = true;
                break;
            default:
                $manualCaptureAllowed = false;
        }

        return $manualCaptureAllowed;
    }

    /**
     * @param $order
     * @return mixed
     */
    protected function _paymentMethodCode($order)
    {
        return $order->getPayment()->getMethod();
    }

    protected function _getPaymentMethodType($order)
    {
        return $order->getPayment()->getMethodInstance()->getPaymentMethodType();
    }

    /**
     * @param $paymentMethod
     * @return bool
     */
    protected function _isBankTransfer($paymentMethod)
    {
        if (strlen($paymentMethod) >= 12 && substr($paymentMethod, 0, 12) == "bankTransfer") {
            $isBankTransfer = true;
        } else {
            $isBankTransfer = false;
        }
        return $isBankTransfer;
    }

    /**
     * @param $order
     */
    protected function _setPaymentAuthorized($order, $manualReviewComment = true, $createInvoice = false, $captureNotification = false)
    {
        $this->_debugData[$this->_count]['_setPaymentAuthorized start'] = 'Set order to authorised';

        // if full amount is captured create invoice
        $currency = $order->getOrderCurrencyCode();
        $amount = $this->_value;
        $orderAmount = (int)Mage::helper('adyen')->formatAmount($order->getGrandTotal(), $currency);

        // create invoice for the capture notification if you are on manual capture
        if ($createInvoice == true && $amount == $orderAmount) {
            $this->_debugData[$this->_count]['_setPaymentAuthorized amount'] = 'amount notification:' . $amount . ' amount order:' . $orderAmount;
            // call createInvoice (this flow can be improved
            $this->_createInvoice($order);
        }

        $autoCapture = $this->_isAutoCapture($order);
        $createPendingInvoice = (bool)$this->_getConfigData('create_pending_invoice', 'adyen_abstract', $order->getStoreId());
        $captureOnShipment = $this->_getConfigData('capture_on_shipment', 'adyen_abstract', $order->getStoreId());

        /**
         * - if create pending invoice is not set just update the status
         * - if create pending invoice is set and the payment method is auto capture update the order
         * - if create pending invoice is set and payment method is manual capture but the notificaiton is a capture notification update the order
         */
        if ($captureOnShipment && !$autoCapture) {
            // if capture on shipment is enabled and it is a manual capture payment method do not update the order
        } else if (!$createPendingInvoice ||
            ($createPendingInvoice && $autoCapture) ||
            ($createPendingInvoice && !$autoCapture && $captureNotification)
        ) {
            $status = $this->_getConfigData('payment_authorized', 'adyen_abstract', $order->getStoreId());

            $this->_debugData[$this->_count]['_setPaymentAuthorized selected status'] = 'The status that is selected is:' . $status;

            // set the state to processing
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
        }

        // virtual order can have different status
        if ($order->getIsVirtual()) {
            $this->_debugData[$this->_count]['_setPaymentAuthorized virtual'] = 'Product is a virtual product';
            $virtual_status = $this->_getConfigData('payment_authorized_virtual');
            if ($virtual_status != "") {
                $status = $virtual_status;

                // set the state to complete (this is not possible because Magento is blocking this will result in: The Order State COMPLETE must not be set manually
//                $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE);
            }
        }

        // check for boleto if payment is totally paid
        if ($this->_paymentMethodCode($order) == "adyen_boleto") {

            // check if paid amount is the same as orginal amount
            $orginalAmount = $this->_boletoOriginalAmount;
            $paidAmount = $this->_boletoPaidAmount;

            if ($orginalAmount != $paidAmount) {

                // not the full amount is paid. Check if it is underpaid or overpaid
                // strip the  BRL of the string
                $orginalAmount = str_replace("BRL", "", $orginalAmount);
                $orginalAmount = floatval(trim($orginalAmount));

                $paidAmount = str_replace("BRL", "", $paidAmount);
                $paidAmount = floatval(trim($paidAmount));

                if ($paidAmount > $orginalAmount) {
                    $overpaidStatus = $this->_getConfigData('order_overpaid_status', 'adyen_boleto');
                    // check if there is selected a status if not fall back to the default
                    $status = (!empty($overpaidStatus)) ? $overpaidStatus : $status;
                } else {
                    $underpaidStatus = $this->_getConfigData('order_underpaid_status', 'adyen_boleto');
                    // check if there is selected a status if not fall back to the default
                    $status = (!empty($underpaidStatus)) ? $underpaidStatus : $status;
                }
            }
        }

        $comment = "Adyen Payment Successfully completed";

        // if manual review is true use the manual review status if this is set
        if ($manualReviewComment == true && $this->_fraudManualReview) {
            // check if different status is selected
            $fraudManualReviewStatus = $this->_getFraudManualReviewStatus($order);
            if ($fraudManualReviewStatus != "") {
                $status = $fraudManualReviewStatus;
                $comment = "Adyen Payment is in Manual Review check the Adyen platform";
            }
        }

        $status = (!empty($status)) ? $status : $order->getStatus();
        $order->addStatusHistoryComment(Mage::helper('adyen')->__($comment), $status);
        $order->sendOrderUpdateEmail((bool)$this->_getConfigData('send_update_mail', 'adyen_abstract', $order->getStoreId()));
        /**
         * save the order this is needed for older magento version so that status is not reverted to state NEW
         */
        $order->save();
        $this->_debugData[$this->_count]['_setPaymentAuthorized end'] = 'Order status is changed to authorised status, status is ' . $status . ' and state is: ' . $order->getState();
    }

    /**
     * @param $order
     */
    protected function _createShipment($order)
    {
        $this->_debugData[$this->_count]['_createShipment'] = 'Creating shipment for order';
        // create shipment for cash payment
        $payment = $order->getPayment()->getMethodInstance();
        if ($order->canShip()) {
            $itemQty = array();
            $shipment = $order->prepareShipment($itemQty);
            if ($shipment) {
                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);
                $comment = Mage::helper('adyen')->__('Shipment created by Adyen');
                $shipment->addComment($comment);
                Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();
                $this->_debugData[$this->_count]['_createShipment done'] = 'Order is shipped';
            }
        } else {
            $this->_debugData[$this->_count]['_createShipment error'] = 'Order can\'t be shipped';
        }
    }

    /**
     * @desc order comments or history
     * @param type $order
     */
    protected function _addStatusHistoryComment($order)
    {
        $success_result = (strcmp($this->_success, 'true') == 0 || strcmp($this->_success, '1') == 0) ? 'true' : 'false';
        $success = (!empty($this->_reason)) ? "$success_result <br />reason:$this->_reason" : $success_result;

        if ($this->_eventCode == Adyen_Payment_Model_Event::ADYEN_EVENT_REFUND || $this->_eventCode == Adyen_Payment_Model_Event::ADYEN_EVENT_CAPTURE) {

            $currency = $order->getOrderCurrencyCode();

            // check if it is a full or partial refund
            $amount = $this->_value;
            $orderAmount = (int)Mage::helper('adyen')->formatAmount($order->getGrandTotal(), $currency);

            $this->_debugData[$this->_count]['_addStatusHistoryComment amount'] = 'amount notification:' . $amount . ' amount order:' . $orderAmount;

            if ($amount == $orderAmount) {
                $order->setAdyenEventCode($this->_eventCode . " : " . strtoupper($success_result));
            } else {
                $order->setAdyenEventCode("(PARTIAL) " . $this->_eventCode . " : " . strtoupper($success_result));
            }
        } else {
            $order->setAdyenEventCode($this->_eventCode . " : " . strtoupper($success_result));
        }

        // if payment method is klarna or openinvoice/afterpay show the reservation number
        if (Mage::helper('adyen')->isOpenInvoice($this->_paymentMethod) && ($this->_klarnaReservationNumber != null && $this->_klarnaReservationNumber != "")
        ) {
            $klarnaReservationNumberText = "<br /> reservationNumber: " . $this->_klarnaReservationNumber;
        } else {
            $klarnaReservationNumberText = "";
        }

        if ($this->_boletoPaidAmount != null && $this->_boletoPaidAmount != "") {
            $boletoPaidAmountText = "<br /> Paid amount: " . $this->_boletoPaidAmount;
        } else {
            $boletoPaidAmountText = "";
        }

        $valueText = '';

        if ($this->_value != null && $this->_value != "") {
            $valueText = "<br /> amount value: " . $this->_value;
        }

        $type = 'Adyen HTTP Notification(s):';
        $comment = Mage::helper('adyen')
            ->__('%s <br /> eventCode: %s <br /> pspReference: %s <br /> paymentMethod: %s <br /> success: %s %s %s %s', $type, $this->_eventCode, $this->_pspReference, $this->_paymentMethod, $success, $valueText, $klarnaReservationNumberText, $boletoPaidAmountText);

        // If notification is pending status and pending status is set add the status change to the comment history
        if ($this->_eventCode == Adyen_Payment_Model_Event::ADYEN_EVENT_PENDING) {
            if ($order->isCanceled() || $order->getState() === Mage_Sales_Model_Order::STATE_HOLDED) {
                $this->_debugData[$this->_count]['_addStatusHistoryComment'] = 'Did not change status because order is already canceled or on hold.';
            } else {
                $pendingStatus = $this->_getConfigData('pending_status', 'adyen_abstract', $order->getStoreId());
                if ($pendingStatus != "") {
                    $order->addStatusHistoryComment($comment, $pendingStatus);
                    /**
                     * save order needed for old magento version so that status is not reverted to state NEW
                     */
                    $order->save();

                    $this->_debugData[$this->_count]['_addStatusHistoryComment'] = 'Created comment history for this notification with status change to: ' . $pendingStatus;
                    return;
                }
            }
        }

        // if manual review is accepted and a status is selected. Change the status through this comment history item
        if ($this->_eventCode == Adyen_Payment_Model_Event::ADYEN_EVENT_MANUAL_REVIEW_ACCEPT
            && $this->_getFraudManualReviewAcceptStatus($order) != ""
        ) {
            $manualReviewAcceptStatus = $this->_getFraudManualReviewAcceptStatus($order);
            $order->addStatusHistoryComment($comment, $manualReviewAcceptStatus);
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
            /**
             * save order needed for old magento version so that status is not reverted to state NEW
             */
            $order->save();
            $this->_debugData[$this->_count]['_addStatusHistoryComment'] = 'Created comment history for this notification with status change to: ' . $manualReviewAcceptStatus;
            return;
        }

        $order->addStatusHistoryComment($comment);
        $this->_debugData[$this->_count]['_addStatusHistoryComment'] = 'Created comment history for this notification';
    }

    /**
     * @param $order
     * @return bool
     * @deprecate not needed already cancelled in ProcessController
     */
    protected function _holdCancelOrder($order, $ignoreHasInvoice)
    {
        $orderStatus = $this->_getConfigData('payment_cancelled', 'adyen_abstract', $order->getStoreId());

        $_mail = (bool)$this->_getConfigData('send_update_mail', 'adyen_abstract', $order->getStoreId());
        $helper = Mage::helper('adyen');

        // check if order has in invoice only cancel/hold if this is not the case
        if ($ignoreHasInvoice || !$order->hasInvoices()) {

            if ($orderStatus == Mage_Sales_Model_Order::STATE_HOLDED) {

                // Allow magento to hold order
                $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_HOLD, true);

                if ($order->canHold()) {
                    $order->hold();
                } else {
                    $this->_debugData[$this->_count]['warning'] = 'Order can not hold or is already on Hold';
                    return;
                }
            } else {

                // Allow magento to cancel order
                $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, true);

                if ($order->canCancel()) {
                    $order->cancel();
                } else {
                    $this->_debugData[$this->_count]['warning'] = 'Order can not be canceled';
                    return;
                }
            }
            $order->sendOrderUpdateEmail($_mail);
        } else {
            $this->_debugData[$this->_count]['warning'] = 'Order has already an invoice so cannot be canceled';
        }
    }

    /*
     * Add AUTHORISATION notification where order does not exists to the queue
     */
    /**
     * @param $params
     */
    protected function _addNotificationToQueue($params)
    {
        $pspReference = $params->getData('pspReference');
        if (is_numeric($pspReference)) {
            $this->_debugData['AddNotificationToQueue Step1'] = 'Going to add notification to queue';

            $incrementId = $params->getData('merchantReference');
            $pspReference = $params->getData('pspReference');
            $eventCode = $params->getData('eventCode');

            // add current request to the queue
            $eventQueue = Mage::getModel('adyen/event_queue');
            $eventQueue->setPspReference($pspReference);
            $eventQueue->setAdyenEventCode($eventCode);
            $eventQueue->setIncrementId($incrementId);
            $eventQueue->setAttempt(1);
            $eventQueue->setResponse(serialize($params));
            $eventQueue->setCreatedAt(now());
            $eventQueue->save();
            $this->_debugData['AddNotificationToQueue Step2'] = 'Notification is added to the queue';

        } else {
            $this->_debugData['AddNotificationToQueue'] = 'Notification is a TEST Notification so do not add to queue';
        }
    }


    /*
     * This function is called from the cronjob
     */
    public function updateNotProcessedNotifications()
    {

        $this->_debugData = array();

        $this->_debugData['processPosResponse begin'] = 'Begin to process cronjob for updating notifications from the queue';

        $this->_updateNotProcessedNotifications();

        $this->_debugData['processPosResponse end'] = 'Cronjob ends';

        return $this->_debugData;
    }

    /**
     *
     */
    protected function _updateNotProcessedNotifications()
    {

        $this->_debugData['UpdateNotProcessedEvents Step1'] = 'Going to update Notifications from the queue';

        // try to update old notifications that did not processed yet
        $collection = Mage::getModel('adyen/event_queue')->getCollection()
            ->addFieldToFilter('attempt', array('lteq' => '4'))
            ->addFieldToFilter('created_at', array(
                'to' => strtotime('-1 minutes', time()),
                'datetime' => true))
            ->addOrder('created_at', 'asc');


        $limit = (int)$this->_getConfigData('event_queue_limit');
        if ($limit > 0) {
            $collection->getSelect()->limit($limit);
        }

        if ($collection->getSize() > 0) {
            $this->_count = 0;
            foreach ($collection as $event) {

                $incrementId = $event->getIncrementId();
                $params = unserialize($event->getResponse());

                // If the event is a RECURRING_CONTRACT wait an extra 5 minutes before processing so we are sure the RECURRING_CONTRACT
                if (trim($params->getData('eventCode')) == Adyen_Payment_Model_Event::ADYEN_EVENT_RECURRING_CONTRACT &&
                    strtotime($event->getCreatedAt()) >= strtotime('-5 minutes', time())
                ) {
                    $this->_debugData[$this->_count]['UpdateNotProcessedEvents end'] = 'This is a recurring_contract notification wait an extra 5 minutes before processing this to make sure the contract exists';
                    $this->_count++;
                    continue;
                }

                $this->_debugData[$this->_count]['UpdateNotProcessedEvents Step2'] = 'Going to update notification with incrementId: ' . $incrementId;

                $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
                if ($order->getId()) {

                    $this->_debugData[$this->_count]['UpdateNotProcessedEvents Step3'] = 'Order exists going to update it';
                    // try to process it now
                    $this->_debugData[$this->_count]['UpdateNotProcessedEvents params'] = $params->debug();

                    // check if notification is already processed
                    if (!$this->_isDuplicate($params)) {
                        try {
                            $this->_updateOrder($order, $params);
                        } catch (\Exception $error) {
                            $this->_debugData[$this->_count]['UpdateNotProcessedEvents updateOrderException'] = $error->getMessage();
                            Mage::logException($error);
                        }
                    } else {
                        // already processed so ignore this notification
                        $this->_debugData[$this->_count]['UpdateNotProcessedEvents duplicate'] = "This notification is already processed so ignore this one";
                    }

                    // update event that it is processed
                    try {
                        // @codingStandardsIgnoreStart
                        $event->delete();
                        // @codingStandardsIgnoreEnd
                        $this->_debugData[$this->_count]['UpdateNotProcessedEvents Step4'] = 'Notification is processed and removed from the queue';
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                } else {
                    // order does not exists remove this from the queue
                    // @codingStandardsIgnoreStart
                    $event->delete();
                    // @codingStandardsIgnoreEnd
                    $this->_debugData[$this->_count]['UpdateNotProcessedEvents Step3'] = 'The Notification still does not exists so it does not have an order remove the notification';
                }

                $this->_count++;
            }
        } else {
            $this->_debugData['UpdateNotProcessedEvents Step2'] = 'The queue is empty';
        }
    }

    /**
     * Log debug data to file
     *
     * @param $storeId
     * @param mixed $debugData
     */
    protected function _debug($storeId)
    {
        if ($this->_getConfigData('debug', 'adyen_abstract', $storeId)) {
            $file = 'adyen_process_notification.log';
            Mage::getModel('core/log_adapter', $file)->log($this->_debugData);
        }
    }

    /**
     * @param $code
     * @param null $paymentMethodCode
     * @param null $storeId
     * @return mixed
     */
    protected function _getConfigData($code, $paymentMethodCode = null, $storeId = null)
    {
        return Mage::helper('adyen')->getConfigData($code, $paymentMethodCode, $storeId);
    }

    /**
     * @return mixed
     */
    protected function _getRequest()
    {
        return Mage::app()->getRequest();
    }
}
