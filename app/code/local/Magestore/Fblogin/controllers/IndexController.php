<?php
class Magestore_Fblogin_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction(){
        //if(!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)){return;}
        Mage::helper('varnishcache/cache')->setNoCacheHeader();
        $isAuth = $this->getRequest()->getParam('auth');
        $facebook = Mage::helper('fblogin')->createFacebook();
        $userId = $facebook->getUser();

        if($isAuth && !$userId && $this->getRequest()->getParam('error_reason') == 'user_denied'){
                echo("<script>window.close()</script>");
        }elseif ($isAuth && !$userId){
                $loginUrl = $facebook->getLoginUrl(array('scope' => 'email'));
                echo "<script type='text/javascript'>top.location.href = '$loginUrl';</script>";
                exit;
        }
        $user = Mage::helper('fblogin')->getFbUser();
        if ($isAuth && $user){
        $nextUrl = Mage::helper('fblogin')->getFbloginUrl();
        if($nextUrl!='')
            die("<script type=\"text/javascript\">window.opener.location.href=\"".$nextUrl."\"; window.close();</script>");
        else
            die("<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href;</script>");
        }
        $store_id = Mage::app()->getStore()->getStoreId(); //add them
        $website_id = Mage::app()->getStore()->getWebsiteId();//add them
        $data =  array('firstname'=>$user['first_name'], 'lastname'=>$user['last_name'], 'email'=>$user['email']);
        $customer = $this->getCustomerByEmail($data['email'], $website_id); //edit
        if(!$customer || !$customer->getId()){
            $customer = $this->createCustomerMultiWebsite($data, $website_id, $store_id);	//add them		
        }
        //add old
        if ($customer->getConfirmation()){
            try {
                $customer->setConfirmation(null);
                $customer->save();
            }catch (Exception $e) {
                Mage::getSingleton('core/session')->addError(Mage::helper('fblogin')->__('Error'));
            }
        }
        Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
        $this->_redirectUrl($this->_loginPostRedirect());//add them fix new
    }
	
	protected function getCustomerByEmail($email, $website_id){//add them
		$collection = Mage::getModel('customer/customer')->getCollection()
			->addFieldToFilter('email', $email);
		if (Mage::getStoreConfig('customer/account_share/scope')) {
			$collection->addFieldToFilter('website_id',$website_id);
		}
		return $collection->getFirstItem();
	}
	
	protected function createCustomer($data){
		$customer = Mage::getModel('customer/customer')
					->setFirstname($data['firstname'])
					->setLastname($data['lastname'])
					->setEmail($data['email']);
					
		$isSendPassToCustomer = Mage::getStoreConfig('fblogin/general/is_send_password_to_customer');
		$newPassword = $customer->generatePassword();
		$customer->setPassword($newPassword);
		try{
			$customer->save();
		}catch(Exception $e){}
		
		if($isSendPassToCustomer)
			$customer->sendPasswordReminderEmail();
		return $customer;
	}
	// add them 
	protected function createCustomerMultiWebsite($data, $website_id, $store_id){
		$customer = Mage::getModel('customer/customer')->setId(null);
		$customer ->setFirstname($data['firstname'])
					->setLastname($data['lastname'])
					->setEmail($data['email'])
					->setWebsiteId($website_id)
					->setStoreId($store_id)
					->save()
					;
		$isSendPassToCustomer = Mage::getStoreConfig('fblogin/general/is_send_password_to_customer');
		$newPassword = $customer->generatePassword();
		$customer->setPassword($newPassword);
		try{
			$customer->save();
		}catch(Exception $e){}
		
		if($isSendPassToCustomer)
			$customer->sendPasswordReminderEmail();
		return $customer;
	}
	//add old
        protected function _loginPostRedirect(){
            $selecturl= Mage::getStoreConfig(('fblogin/general/select_url'),Mage::app()->getStore()->getId());
            if($selecturl==0) return Mage::getUrl('customer/account');
            if($selecturl==2) return Mage::getUrl();
            if($selecturl==3) return Mage::getSingleton('core/session')->getFbCurrentpage();
            if($selecturl==4) return Mage::getStoreConfig(('fblogin/general/custom_page'),Mage::app()->getStore()->getId());
            if($selecturl==1 && Mage::helper('checkout/cart')->getItemsCount()!=0) return Mage::getUrl('checkout/cart');else return Mage::getUrl();
	}
}