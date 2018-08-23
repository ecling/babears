<?php

class Martin_Flytcloud_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_shippingTypeCountries;
    protected $_shippingTypes;
    protected $_addressCompareFields=array(
        'region','postcode','street','city','email','telephone','country_id',
    );

    public function generateName($address)
    {
        $names=array();
        $firstName=trim($address->getData("firstname"));
        if($firstName)$names[]=$firstName;
        
        $middleName=trim($address->getData('middlename'));
        if($middleName)$names[]=$middleName;
        
        $lastName=trim($address->getData("lastname"));
        if($lastName)$names[]=$lastName;

        array($firstName,$middleName,$lastName);
        

        return implode(" ", $names);
    }
    public function isSameAddress(Mage_Sales_Model_Order $order)
    {
        $shippingAddress=$order->getShippingAddress();
        $billingAddress=$order->getBillingAddress();
        

        foreach($shippingAddress->getData() as $key=>$val){
            if(!in_array($key, $this->_addressCompareFields)) continue;
            if($val!==$billingAddress->getData($key)){
                return false;
            }
            
        }
        
        if($this->generateName($shippingAddress)!==$this->generateName($billingAddress))
        {
            return false;
        }
        return true;
    }
    public function getShppingTypeOptions()
    {
        $collection=Mage::getModel('flytcloud/shipping_type')->getCollection();
        $optionArr=$collection->toOptionArray();
        $options=array();
        foreach($optionArr as $item){
            $options[$item['value']]=$item['label'];
        }
        return $options;
    }
    public function getCountryOptions()
    {
        $optionArr= Mage::getResourceModel('directory/country_collection')->loadData()->toOptionArray(false);
        $options=array();
        foreach($optionArr as $item){
            $options[$item['value']]=$item['label'];
        }
        return $options;
    }
    public function getCountryById($countrId)
    {
        return Mage::app()->getLocale()->getCountryTranslation($countrId);
    }
    public function getShippingTypeCodeByName($name)
    {
        if(!$this->_shippingTypes){
            $this->_shippingTypes=Mage::getModel('flytcloud/shipping_type')->getCollection()->load();
        }
        foreach($this->_shippingTypes as $item){
            if($name===$item->getData('shipping_type')){
                return $item->getData('shipping_type_code');
            }
        }
        return null;
    }
    public function getShippingTypeCodeByCountryId($countryId)
    { 
        if(!$this->_shippingTypeCountries){
            $collection=Mage::getModel('flytcloud/shipping_type_country')->getCollection()->load();
            $this->_shippingTypeCountries=$collection;
        }
       
        foreach($this->_shippingTypeCountries as $item){
            if($item->getData('country_id')===$countryId){
                $shippingType=$item->getData('shipping_type');
                return $this->getShippingTypeCodeByName($shippingType);
            }
        }
       
        return null;
    }
    

    public function _generateRequestXml($order)
    {
        $orderHelper=Mage::helper('flytcloud/order');
        return $orderHelper->generateApiRequestXml($order);
    }
    
    protected function _doUploadToFlytcloud($order)
    {
        try{
            $xml = $this->_generateRequestXml($order);
            $Api=Mage::getModel('flytcloud/apiclient');
            Mage::log($order->getIncrementId(),null,'test.log');
            Mage::log($xml,null,'test.log');
            $response=$Api->callWebServer($xml);

            if($this->_isUploadSuccess($response))
            {
                 return $response;
            }

            $message="order ".$order->getData("increment_id")."upload failed";
            $message.="\r\n ".$response->getData(Martin_Flytcloud_Model_Api_Response::NODE_MESSAGE);
            throw new Martin_Flytcloud_Model_Exception_UploadFailed($message);
        }catch (Exception $e)
        {
            throw new Martin_Flytcloud_Model_Exception_UploadFailed($e->getMessage());
        }
    }
    /**
     * 
     * @param Mage_Sales_Model_Order $order
     * @return String  FlytcloudOrderID
     */
    public function submitOrderToFlytcloud(Mage_Sales_Model_Order $order)
    {
        try{
            $orderNum=$order->getData('increment_id');
            $successResponse=$this->_doUploadToFlytcloud($order);
            if($successResponse)
            {
                if($this->_recordSuccess($successResponse,$order))
                {
                    //$trackId=$this->_logTrack($successResponse,$order);
                    //$this->_informCustomer($successResponse,$order);
                    //if($successResponse->getData('Message')=='上传成功')
                    //通过返回结果的Flag判断是否获取成功
                    if($successResponse->getData('Flag'))
                    {
                        $shippingType=Mage::helper('flytcloud/shippingtype')->getShippingTypeByOrder($order);
                        //$track=array('carrier_code'=>'custom','number'=>$trackId,'title'=>$shippingType->getData('shipping_type_code'));
                        //将标题固定为FLYT，将number设置为飞特订单号
                        $trackId = $successResponse->getData(Martin_Flytcloud_Model_Api_Response::NODE_ORDERID);
                        $track=array('carrier_code'=>'custom','number'=>$trackId,'title'=>'FLYT');
                        Mage::dispatchEvent("order_update_load_to_shipper_success",array('order'=>$order,'tracks'=>array($track)));
                    }
                }
                return true;
            }else{
                return false;
            }
        } catch (Martin_Flytcloud_Model_Exception_UploadFailed $ex) {
                Mage::log($ex->getMessage(),null,'flytcloud_upload.log');
                return false;
                //return "\r\n order ".$orderNum." Upload failed!";
          }catch(Martin_Flytcloud_Model_Exception_Record $ex){
                Mage::log($ex->getMessage(),null,'flytcloud_record_status.log');
                return false;
                //return "\r\n order ".$orderNum." Upload Success,but record into Magento system failed!";
          }catch(Martin_Flytcloud_Model_Exception_NoticeOfTrack $ex){
               Mage::log($ex->getMessage(),null,'flytcloud_track_notice.log');
               return false;
               //return "\r\n order ".$orderNum." Upload Success,but send email to customer failed!";
          }
    }
    
    protected function _isUploadSuccess(Martin_Flytcloud_Model_Api_Response $response)
    {
        $flag=$response->getData(Martin_Flytcloud_Model_Api_Response::NODE_FLAG);
        return $flag===Martin_Flytcloud_Model_Api_Response::SUCCESS_FLAG?true:false;
    }
    protected function _recordSuccess($response,$order)
    {
        try{
            $flytcloudOrderId=$response->getData(Martin_Flytcloud_Model_Api_Response::NODE_ORDERID);
            $statusCode='Submitted to Ship';
            $shippingStatusModel=Mage::getModel('flytcloud/shipping_status')->load($statusCode,'shipping_status');
            $orderId=$order->getId();
            
            $shippingStatusId=$shippingStatusModel->getId();
            $orderShippingStatus=Mage::getModel('flytcloud/order_shipping_status');
            $collection=$orderShippingStatus->getCollection()
                    ->addFieldToFilter('order',array('eq'=>$orderId));

            $item=$collection->fetchItem();
            if($item){
                $orderShippingStatus=$item;
            }
            

            $orderShippingStatus ->setShippingStatus($shippingStatusId)
                    ->setOrder($orderId)
                    ->setData('flytcloud_order_id',$flytcloudOrderId)
                    ->save();
            return true;
        } catch (Exception $ex) {
            $message="\r\n ".$ex->getMessage();
            $message.="\r\n Model:fltycloud/order_shipping_status";
            $message.="\r\n data:".json_encode($orderShippingStatus->debug());
             throw new Martin_Flytcloud_Model_Exception_Record($message);
        }
    }
    protected  function _informCustomer($response,$order)
    {
        try{
            $traceId=$response->getData(Martin_Flytcloud_Model_Api_Response::NODE_TRACEID);
            if($traceId)
            {   
                if(is_string($traceId))
                {
                    $email=$order->getShippingAddress()->getData('email');

                    $sender=Mage_Customer_Model_Customer::XML_PATH_REGISTER_EMAIL_IDENTITY;

                    $template='flytcloudbasic/inform/track_email_template';
                    
                    $storeId=$order->getData('store_id');
                    $templateId=Mage::getStoreConfig($template,$storeId);
                    $sender=Mage::getStoreConfig($sender);

                    if($this->isDev())
                    {
                        $email=$this->getDevSetting('customerEmail',true);
                    }
                        /** @var $mailer Mage_Core_Model_Email_Template_Mailer */
                    $mailer = Mage::getModel('core/email_template_mailer');
                    $emailInfo = Mage::getModel('core/email_info');
                    $name=$order->getShippingAddress()->getFirstname();
                    $name.=" ".$order->getShippingAddress()->getLastname();
                    $orderNum=$order->getData('increment_id');
                    
                    $paymentBlock = Mage::helper('payment')->getInfoBlock($order->getPayment())
                        ->setIsSecureMode(true);
                    $paymentBlock->getMethod()->setStore($storeId);
                    $paymentBlockHtml = $paymentBlock->toHtml();
                    

                    $emailInfo->addTo($email, $name);
                    $mailer->addEmailInfo($emailInfo);
                    // Set all required params and send emails
                    $mailer->setSender($sender);
                    //$mailer->setStoreId($storeId);
                    $mailer->setTemplateId($templateId);
                    $mailer->setTemplateParams(array('track_id'=>$traceId,'order_num'=>$orderNum,'order'=>$order,
                        'payment_html'=>$paymentBlockHtml));
                    
                    $mailer->send();
                }else{
                    $message="no track type is not expected.";
                    $message.="order increment id is ".$order->getData('increment_id');
                    throw new Martin_Flytcloud_Model_Exception_NoticeOfTrack($message);
                }
            }else{
                $message='no track id is returned.';
                $message.="order increment id is ".$order->getData('increment_id');
                throw new Martin_Flytcloud_Model_Exception_NoticeOfTrack($message);
            }
            return true;
        } catch (Exception $ex) {
            $message=$ex->getMessage();
            $message="\r\n send notice email to customer failde after upload order to flytcloud,template/email/sender is required";
            $message.="\r\n orderNum:$orderNum  track id:$traceId email:$email  "
                    . "\r\n templatePath:$template  templateId:$templateId  senderPath:$sender  senderId:$senderId ";
            throw new Martin_Flytcloud_Model_Exception_NoticeOfTrack($message);
        }
    }
    
    public function isDev()
    {
        $idDev=Mage::app()->getConfig()->getNode('flytcloud/devSetting/active');
        return $idDev?true:false;
    }
    public function getDevSetting($node,$string=false)
    {
        if($this->isDev())
        {
            $cfg=Mage::app()->getConfig()->getNode("flytcloud/devSetting/$node");
            return $string?(string)$cfg:$cfg;
        }else{
            return null;
        }
    }
    
    
    protected function _logTrack($response,$order,$return=true)
    {
            $traceId=$response->getData(Martin_Flytcloud_Model_Api_Response::NODE_TRACEID);
            $orderIncrement=$order->getData('increment_id');
            if($traceId)
            {   
                if(is_string($traceId))
                {
                      $file=Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA).DS.'track.log';
                      $handle=fopen($file,'a+');
                      if($handle)
                      {
                        fwrite($handle, "\r\n trackId:$traceId   orderIncrementId:".$orderIncrement);
                        fclose($handle);  
                        return $traceId;
                      }else{
                          throw new Exception("couldn't open log file,log track id '$traceId' failed for order !");
                      }
                }
            }
    }
}
