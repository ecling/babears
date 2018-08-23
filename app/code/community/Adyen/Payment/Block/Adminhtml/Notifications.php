<?php

class Adyen_Payment_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
    protected $_authSession;
    protected $_cronCheck;
    protected $_dateChecked;
    protected $_adyenHelper;

    public function _construct(){
        $this->_authSession = Mage::getSingleton('admin/session');
        $this->_cronCheck = $this->getSessionData("cronCheck");
        $this->_dateChecked = $this->getSessionData("dateChecked");
        $this->_adyenHelper = Mage::helper('adyen');
    }


    public function getMessage()
    {
       //check if it is after first login
        if($this->_authSession->isFirstPageAfterLogin()) {
            //get store timezone
            $timezone = new DateTimeZone(Mage::getStoreConfig(
                Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE
            ));
            // do logic and put in session if no result destroy it from the session
            $this->_dateChecked = new DateTime('now', $timezone);
            $this->_cronCheck = $this->_adyenHelper->getUnprocessedNotifications();
            $this->setSessionData("cronCheck", $this->_cronCheck);
            $this->setSessionData("dateChecked", $this->_dateChecked);
        }

        //check if there are unprocessed notifications in queue
        if($this->_cronCheck > 0) {
            $message = ('You have ' . $this->_cronCheck . ' unprocessed notification(s). Please check your Cron ');
            $message .= "and visit <a href='http://devdocs.magento.com/guides/m1x/install/installing_install.html#install-cron' target='_blank'>Magento DevDocs</a> and 
                    <a href='https://docs.adyen.com/developers/plug-ins-and-partners/magento/magento-1/configure-the-adyen-plug-in' target='_blank'>Adyen Docs</a> on how to configure Cron.";
            $message .= "<i> Last  cron check was: " . $this->_dateChecked->format('Y/m/d H:i:s') . "</i>";
            return $message;
        }
        else {
            return;
        }

    }

    /**
     * Set the current value for the backend session
     */
   public function setSessionData($key, $value)
    {
        return $this->_authSession->setData($key, $value);
    }

   /**
    * Retrieve the session value
    */
   public function getSessionData($key, $remove = false)
    {
        return $this->_authSession->getData($key, $remove);
    }
}