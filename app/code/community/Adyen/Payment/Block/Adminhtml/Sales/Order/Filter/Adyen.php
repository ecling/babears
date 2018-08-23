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
 * @category	Adyen
 * @package	Adyen_Payment
 * @copyright	Copyright (c) 2011 Adyen (http://www.adyen.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */
class Adyen_Payment_Block_Adminhtml_Sales_Order_Filter_Adyen extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Select {

    const EVENT_CODES_CACHE_KEY = 'adyen_event_codes';

    protected function _getOptions()
    {
        $events = array(
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_CAPTURE),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_CAPTURE_FAILED),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_REFUND),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_CANCEL_OR_REFUND),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_AUTHORISATION),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_RECURRING_CONTRACT),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_NOF),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_NOC),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_OFFER_CLOSED),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_HANDLED_EXTERNALLY),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_AUTHORISED_API),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_REDIRECT_SHOPPER),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_CAPTURE_RECEIVED),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_CANCEL_OR_REFUND_RECEIVED),
            array('adyen_event_result' => Adyen_Payment_Model_Event::ADYEN_EVENT_REFUND_RECEIVED)
        );

        $select = array(
            array('label' => '', 'value' => ''),
            array('label' => 'N.A', 'value' => 'N.A'),
        );
        foreach ($events as $event) {


            switch($event['adyen_event_result']) {

                case Adyen_Payment_Model_Event::ADYEN_EVENT_CAPTURE:
                case Adyen_Payment_Model_Event::ADYEN_EVENT_CAPTURE_FAILED:
                case Adyen_Payment_Model_Event::ADYEN_EVENT_REFUND:
                case Adyen_Payment_Model_Event::ADYEN_EVENT_CANCEL_OR_REFUND:
                case Adyen_Payment_Model_Event::ADYEN_EVENT_AUTHORISATION:
                case Adyen_Payment_Model_Event::ADYEN_EVENT_NOF:
                case Adyen_Payment_Model_Event::ADYEN_EVENT_NOC:
                case Adyen_Payment_Model_Event::ADYEN_EVENT_RECURRING_CONTRACT:

                    if ($event['adyen_event_result'] == Adyen_Payment_Model_Event::ADYEN_EVENT_REFUND) {
                        $eventNamePartialTrue = "(PARTIAL) " . $event['adyen_event_result'] . " : " . "TRUE";
                        $eventNamePartialFalse = "(PARTIAL) " . $event['adyen_event_result'] . " : " . "FALSE";

                        $select[] = array('label' => $eventNamePartialTrue, 'value' => $eventNamePartialTrue);
                        $select[] = array('label' => $eventNamePartialFalse, 'value' => $eventNamePartialFalse);
                    }

                    $eventNameTrue = $event['adyen_event_result'] . " : " . "TRUE";
                    $eventNameFalse = $event['adyen_event_result'] . " : " . "FALSE";
                    $select[] = array('label' => $eventNameTrue, 'value' => $eventNameTrue);
                    $select[] = array('label' => $eventNameFalse, 'value' => $eventNameFalse);

                    break;
                default:
                    $select[] = array('label' => $event['adyen_event_result'], 'value' => $event['adyen_event_result']);
                    break;
            }
        }
        return $select;
    }


    public function getCondition()
    {
        if($this->getValue() == "N.A") {
            return array('null' => "");
        }
        return $this->getValue();
    }

}
