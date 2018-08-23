<?php

class Martin_Flytcloud_Model_Apiclient{
     protected function _wsdl()
     {
         $wsdl=Mage::getStoreConfig('flytcloudbasic/api/wsdl');
         return $wsdl;
     }
     public function xml_to_array($xml,$main_heading = '') {
		$deXml = simplexml_load_string($xml);
		$deJson = json_encode($deXml);
		$xml_array = json_decode($deJson,TRUE);
		if (! empty($main_heading)) {
		        $returned = $xml_array[$main_heading];
		        return $returned;
		    } else {
 		       return $xml_array;
		    }
	}
	 
	public function callWebServer($xml){ 
        try{
    		$client = new SoapClient($this->_wsdl());
    		$params = array("request" => $xml);
    
    		$result = $client->Upload($params);
            
            /*        
            if(Mage::helper('flytcloud')->isDev())
            {
                Mage::log((string)$result->UploadResult ,null , 'apiResponse.log') ;
            }
            */
            
            Mage::log((string)$result->UploadResult ,null , 'test.log') ;
    
    		$return = $this->xml_to_array($result->UploadResult);
    		$response[Martin_Flytcloud_Model_Api_Response::NODE_FLAG] =  $return['Flag'];
    		$response[Martin_Flytcloud_Model_Api_Response::NODE_MESSAGE] =  $return['Cause'];
    		if($return['Flag'] == 1){
                        $response[Martin_Flytcloud_Model_Api_Response::NODE_TRACEID]='';
                        $response[Martin_Flytcloud_Model_Api_Response::NODE_ORDERID] =  $return['SuccList']['SuccOrder']['OrderId'];
                        $response[Martin_Flytcloud_Model_Api_Response::NODE_TRACEID] =  $return['SuccList']['SuccOrder']['TraceId'];
    		}
            $responseObj=Mage::getModel('flytcloud/api_response');
            $responseObj->setData($response);
            return $responseObj;
            //return json_encode($response);
        } catch (Exception $ex) {
            throw new Martin_Flytcloud_Model_Exception_ApiRequest($ex->getMessage());
        }
	}
}

