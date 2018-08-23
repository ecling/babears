<?php

class Adyen_Payment_Block_Adminhtml_Messageversion extends Mage_Adminhtml_Block_Template
{
    protected $_authSession;
    protected $_adyenHelper;
    protected $_inbox;

    public function _construct()
    {
        $this->_authSession = Mage::getSingleton('admin/session');
        $this->_adyenHelper = Mage::helper('adyen');
        $this->_inbox = Mage::getModel('adminnotification/inbox');
    }


    public function getMessage()
    {
        //check if it is after first login
        if ($this->_authSession->isFirstPageAfterLogin()) {

            try {
                $githubContent = $this->getDecodedContentFromGithub();
                $title = "Adyen extension version " . $githubContent['tag_name'] . " available!";
                $versionData[] = array(
                    'severity' => Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE,
                    'date_added' => $githubContent['published_at'],
                    'title' => $title,
                    'description' => $githubContent['body'],
                    'url' => $githubContent['html_url'],
                );

                /*
                 * The parse function checks if the $versionData message exists in the inbox,
                 * otherwise it will create it and add it to the inbox.
                 */
                $this->_inbox->parse(array_reverse($versionData));

                /*
                 * This will compare the currently installed version with the latest available one.
                 * A message will appear after the login if the two are not matching.
                 */
                if ($this->_adyenHelper->getExtensionVersion() != $githubContent['tag_name']) {
                    $message = "A new Adyen extension version is now available: ";
                    $message .= "<a href= \"" . $githubContent['html_url'] . "\" target='_blank'> " . $githubContent['tag_name'] . "!</a>";
                    $message .= " You are running the " . $this->_adyenHelper->getExtensionVersion() . " version. We advise to update your extension.";
                    return $message;
                }
            } catch (Exception $e) {
                return;
            }
        }
        return;

    }

    public function getDecodedContentFromGithub()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/adyen/adyen-magento/releases/latest');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'magento');
        $content = curl_exec($ch);
        curl_close($ch);
        $json = json_decode($content, true);
        return $json;
    }
}