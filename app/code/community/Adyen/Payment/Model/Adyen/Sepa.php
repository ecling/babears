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
class Adyen_Payment_Model_Adyen_Sepa extends Adyen_Payment_Model_Adyen_Abstract
    implements Mage_Payment_Model_Billing_Agreement_MethodInterface {

    protected $_code = 'adyen_sepa';
    protected $_formBlockType = 'adyen/form_sepa';
    protected $_infoBlockType = 'adyen/info_sepa';
    protected $_paymentMethod = 'sepa';
    protected $_canCreateBillingAgreement = true;

    /**
     * Called everytime the adyen_sepa is called or used in checkout
     * @descrition Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data) {

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();

        $info->setAdditionalInformation('account_name', $data->getAccountName());
        $info->setAdditionalInformation('iban', $data->getIban());
        $info->setAdditionalInformation('country', $data->getCountry());
        $info->setAdditionalInformation('accept_sepa', $data->getAcceptSepa());

        $info->setCcOwner($data->getOwner())
                ->setCcType($data->getBankLocation())
                ->setCcLast4(substr($data->getAccountNumber(), -4))
                ->setCcNumber($data->getAccountNumber())
                ->setCcNumberEnc($data->getBankCode());

        return $this;
    }

    /**
     * Validate Form
     * 
     * @return $this
     */
    public function validate()
    {
        parent::validate();

        $info = $this->getInfoInstance();

        if(!$info->getAdditionalInformation('accept_sepa')) {
            $errorMsg = Mage::helper('adyen')->__('Please accept the conditions for a SEPA direct debit.');
            Mage::throwException($errorMsg);
        }
        // check if validator is on
        $ibanValidation = $this->_getConfigData("validate_iban", "adyen_sepa");

        if($ibanValidation) {
            $iban = $info->getAdditionalInformation('iban');

            if (!$this->validateIban($iban) || empty($iban)) {
                Mage::throwException(Mage::helper('adyen')->__('Invalid Iban number.'));
            }
        }
        return $this;
    }

    /**
     * Validate IBAN
     * 
     * @param $iban
     * @return bool
     */
    public function validateIban($iban) {

        $iban = strtolower(str_replace(' ','',$iban));
        $Countries = array('al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24);
        $Chars = array('a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35);

        if(isset($Countries[substr($iban,0,2)]) && strlen($iban) == $Countries[substr($iban,0,2)]){

            $MovedChar = substr($iban, 4).substr($iban,0,4);
            $MovedCharArray = str_split($MovedChar);
            $NewString = "";

            foreach($MovedCharArray AS $key => $value){
                if(!is_numeric($MovedCharArray[$key])){
                    $MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
                }
                $NewString .= $MovedCharArray[$key];
            }

            if(bcmod($NewString, '97') == 1)
            {
                return TRUE;
            }
            else{
                return FALSE;
            }
        }
        else{
            return FALSE;
        }
    }

    public function canCreateAdyenSubscription() {

        // validate if recurringType is correctly configured
        $recurringType = $this->_getConfigData('recurringtypes', 'adyen_abstract');
        if($recurringType == "RECURRING" || $recurringType == "ONECLICK,RECURRING") {
            return true;
        }
        return false;

        // TODO: add config where merchant can set the payment types that are available for subscription
    }
    /**
     * @return bool
     */
    public function isBillingAgreement()
    {
        return true;
    }


}
