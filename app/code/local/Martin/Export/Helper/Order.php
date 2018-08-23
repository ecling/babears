<?php

class Martin_Export_Helper_Order extends Mage_Core_Helper_Abstract
{
    protected $_formatHelper;
    public function groupByCigProduct(Mage_Sales_Model_Resource_Order_Collection $orders)
    {
        foreach($orders as $order)
        {
            $order->getAllVisibleItems();
        }
    }
    
    
    public function _prepareData($orderCollection)
    {
        $colorLable="Color";
        $sizeLabel='Size';
        $orderHelper=Mage::helper('flytcloud/order');
        
        $productsOrdered=array();//array($productId=>array("name"=>$name,'sku'=>$sku,'items'=>array(array("color"=>$color,'size'=>$size,'qty'=>$qty))););
        
        //foreach($orderCollection as $order)
        //{
            foreach($orderCollection as $item)
            {

                $optionsArr=$this->_initBuyOptionsArr($item);
                
                $sku=$item->getSku();
                $name=$item->getName();
                $productId=$item->getProductId();

               
                //$color=$orderHelper->getAttrTextFromOrderItem($item,"Color");
                //$size=$orderHelper->getAttrTextFromOrderItem($item,"Size");
                
                //$qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();
                $qty = $item->getQtyOrdered();
                
                $unitKey=$this->_unitKey($optionsArr);
                $unitKey=$unitKey?$unitKey:'-';
                
                if(!isset($productsOrdered[$productId])){
                    $productsOrdered[$productId]['name']=$name;
                    $productsOrdered[$productId]['sku']=$sku;
                    $productsOrdered[$productId]['optionTitles']=array_keys($optionsArr);
                    $productsOrdered[$productId]['image'] = $item->getImage();
                }
                if(!$productsOrdered[$productId]['items'][$unitKey]){
                   // $productsOrdered[$productId]['items'][$unitKey]=array('color'=>$color,'size'=>$size,'qty'=>$qty);
                    $productsOrdered[$productId]['items'][$unitKey]=array('options'=>$optionsArr,'qty'=>$qty);
                }else{
                    $qty+=$productsOrdered[$productId]['items'][$unitKey]['qty'];
                    $productsOrdered[$productId]['items'][$unitKey]=array('options'=>$optionsArr,'qty'=>$qty);
                }
                
            }
        //}
        return $this->sortOptions($productsOrdered);
    }
    
    public function sortOptions($productsOrdered){
        foreach($productsOrdered as $product_id=>$productData){
            if(isset($productData['items'])){
                $options = $productData['items'];
                //krsort($options);
                uksort($options,array($this,'sortSize'));
                $productsOrdered[$product_id]['items'] = $options;
            }
        }
        return $productsOrdered;
    }
    
    public function sortSize($a,$b){
        $array = array('XS','S','M','L','XL','XXL','2XL','XXXL','3XL','XXXXL','4XL');
        $a_key = explode('-',$a);
        $b_key = explode('-',$b);
        foreach($a_key as $key=>$value){
            if(array_search($value,$array)===false){
                $color = strnatcmp($a_key[$key],$b_key[$key]);
                if($color>0){
                    return 1;
                    break;
                }elseif($color<0){
                    return -1;
                    break;
                }
            }else{
                $size = array_search($a_key[$key],$array)>array_search($b_key[$key],$array);
                if($size>0){
                    return 1;
                    break;
                }elseif($size<0){
                    return -1;
                    break;
                }
            }
        }
        return 0;
    }
    
    /*
    public function sortSize($a,$b){
        $array = array('XS','S','M','L','XL','XXL','2XL','XXXL','3XL','XXXXL','4XL');
        $a_key = explode('-',$a);
        $b_key = explode('-',$b);
        if(isset($a_key['0'])){
            $color = strnatcmp($a_key['0'],$b_key['0']);
            if($color==0){
                if(isset($a_key['1'])){
                    $size = array_search($a_key['1'],$array)>array_search($b_key['1'],$array);
                    if($size==0){
                        return 0;
                    }elseif($size>0){
                        return 1;
                    }else{
                        return -1;
                    }
                }else{
                    return 0;
                }   
            }elseif($color>0){
                return 1;
            }else{
                return -1;
            }
        }else{
            return 0;
        }
    }
    */
    
    protected function _unitKey($buyOptions)
    {
        $key='';
        foreach($buyOptions as $lable=>$val)
        {
            $key.= "$val-";
        }
        return trim($key,'-');
    }

    protected function _initBuyOptionsArr($item)
    {
        $orderHelper=Mage::helper('flytcloud/order');
        $options=$orderHelper->getBuyOptions($item);
       
        $optionsArr=array();
        foreach($options as $option)
        { 
            if(isset($option['label']) && isset($option['value']))
            {
                $optionsArr[$option['label']]=$option['value'];
            }
        }
        return $optionsArr;    
    }

    
    
    protected function _formatHeler()
    {
        if(!$this->_formatHelper){
            $this->_formatHelper=Mage::helper('martinexport/order_format');
        }
        return $this->_formatHelper;
    }
    public function setGlobalStyles(Array $styles)
    {
        $formatHelper=$this->_formatHeler();
        foreach($styles as $key=>$val){
            $formatHelper->setStyleVal($key,$val);
        }
    }
    
    
    protected function _titles($optionLabels)
    {
//        $titles=array("产品图片","产品名称");
//        $titles2=array("数量","1688订单号");
        $titles=array("Product Image","Product Name");
        $titles2=array("qty","1688订单号");
        return array_merge($titles,$optionLabels,$titles2);
    }
    protected $_minZoneColsNum;
    
    protected function _iniMinZoneColsNum($productsOrdered)
    {
        foreach($productsOrdered as $productOrdered)
        {
                $optionLabels=isset($productOrdered['optionTitles'])?$productOrdered['optionTitles']:null;
                $titles=$this->_titles($optionLabels);
                $this->_minZoneColsNum=max($this->_minZoneColsNum,count($titles));
        }
    }
    public function exportToExcel($collection,$filename)
    {
       $folderPath=$this->_exportTo();
       $file=$this->_generateFile($folderPath,$filename);
       
       if(Mage::helper('martinexport/excel')->isExcelPluginIncluded())
       {
            $formatHelper=$this->_formatHeler();
            //$titles=array("Product Image","Product Name","Color","Size","Quantity","1688订单号");
            $formatHelper->setStyleVal(Martin_Export_Helper_Order_Format::STYLE_FONT_SIZE,11);
            $formatHelper->getStyle('A1')->getFont()->setBold(true);
            $formatHelper->getStyle('B1')->getFont()->setBold(true);
            
            //$titles=array("产品图片","产品名称","颜色","尺码","数量","1688订单号");
            
            
            $productsOrdered=$this->_prepareData($collection);

            $this->_iniMinZoneColsNum($productsOrdered);
            $formatHelper->setMinZoneColsNum($this->_minZoneColsNum);
            static $timerZone=1;


            foreach($productsOrdered as $productId=>$productOrdered)
            {
                $optionLabels=isset($productOrdered['optionTitles'])?$productOrdered['optionTitles']:null;
                $titles=$this->_titles($optionLabels);
                
                
                $even=$timerZone%2?0:1;
                $productZone=new Martin_Export_Model_Format_Zone();

               // $productZone->setColsNum(count($titles));
                
                
                $productZone->setTitles($titles);
                
                
                $productZone->setRowsNum(count($productOrdered['items'])+1);

                    


                $formatHelper->setCurrentZone($productZone,$even);

                $statCell=$formatHelper->getCurrentZone()->getStartCell();
                
//                static $timer=0;
//                $timer++;
//                if(2===$timer){
//                   var_dump($statCell);exit; 
//                }


                //写titles
                $formatHelper->focusOn($statCell);
                foreach($titles as $i=>$title)
                {
                    if($i!==0){
                        $formatHelper->rightMove($formatHelper->getFocusOn());
                    }
                    $currentCell=$formatHelper->getFocusOn();
                    $translation=array("Product Image"=>"产品图片","Product Name"=>"产品名称",
                                "Size"=>"尺寸",
                                "qty"=>"数量",
                                "Color"=>"颜色",
                                "Style"=>"款式");

                    $title=isset($translation[$title])?$translation[$title]:$title;
                    $formatHelper->setValue($currentCell,$title);
                    if('1688订单号'===$title){
                        $rgb='CD661D';
                        $formatHelper->getStyle($currentCell)->getFont()->getColor()->setRGB($rgb);
                        $formatHelper->downMove($formatHelper->getFocusOn(),2);
                        $formatHelper->setValue($formatHelper->getFocusOn(),"物流追踪号");
                        $formatHelper->getStyle($formatHelper->getFocusOn())->getFont()->getColor()->setRGB($rgb);
                        $formatHelper->downMove($formatHelper->getFocusOn(),2);
                        $formatHelper->setValue($formatHelper->getFocusOn(),"备注");
                        $formatHelper->getStyle($formatHelper->getFocusOn())->getFont()->getColor()->setRGB($rgb);
                        $formatHelper->focusOn($currentCell);
                    }
                    $formatHelper->getStyle($currentCell)->getFont()->setBold(true);
                }

                $formatHelper->downMove($formatHelper->getFocusOn());
                $formatHelper->moveToCol($formatHelper->getFocusOn(),$formatHelper->getCol($statCell));

                //写img
                     //to do
                $img=$this->_productImgPath($productOrdered['image']);
                if(file_exists($img))
                {
                    $objDrawing = new PHPExcel_Worksheet_Drawing();
                    $objDrawing->setPath($img);
                    $objDrawing->setHeight(150);
                    $objDrawing->setCoordinates($formatHelper->getFocusOn());
                    $objDrawing->setWorksheet($formatHelper->getExcel()->getActiveSheet());
                }else{
                   $formatHelper->setValue($formatHelper->getFocusOn(),$img);
                }
                $formatHelper->getExcel()->getActiveSheet()->getColumnDimension($formatHelper->getCol($formatHelper->getFocusOn()))
                        ->setWidth(22);  


                //写product name&& sku
                     $formatHelper->rightMove($formatHelper->getFocusOn());
                     $formatHelper->setValue($formatHelper->getFocusOn(),"{$productOrdered['name']} \n{$productOrdered['sku']}");
                     $formatHelper->getStyle($formatHelper->getFocusOn())->getAlignment()->setWrapText(true);
                $formatHelper->getExcel()->getActiveSheet()->getColumnDimension($formatHelper->getCol($formatHelper->getFocusOn()))
                        ->setWidth(23);  

                //写simple product  
                   //  $formatHelper->rightMove($formatHelper->getFocusOn());
                
                     foreach($productOrdered['items'] as $item)
                     {
                         
//                         $color=isset($item['color'])?$item['color']:null;
//                         $size=isset($item['size'])?$item['size']:null;
                           $qty=isset($item['qty'])?$item['qty']:null;
                           $options=isset($item['options'])?$item['options']:null;

//                          $simpleItemStartCol=$formatHelper->getCol($formatHelper->getFocusOn());
//                          $formatHelper->setValue($formatHelper->getFocusOn(),$color);
//                          $formatHelper->rightMove($formatHelper->getFocusOn());
//                          $formatHelper->setValue($formatHelper->getFocusOn(),$size);
//                          $formatHelper->rightMove($formatHelper->getFocusOn());
                          
                          $simpleItemStartCol=null;
                          $row=$formatHelper->getRow($formatHelper->getFocusOn());
                          
                          if($options)
                          {
                              foreach($options as $label=>$val)
                              {
                                  $col=$this->_getColByTitle($label,$formatHelper->getCurrentZone());
                                  $formatHelper->setValue($col.$row,$val);
                                  //var_dump($productId.'---'.$col.'-----'.$row.'-----'.$val);
                                  if(is_null($simpleItemStartCol)){
                                      $simpleItemStartCol=$col;
                                  }
                              }
                          }
                          
                          $col=$this->_getColByTitle('qty',$formatHelper->getCurrentZone());

                          $formatHelper->setValue($col.$row,$qty);        
                          if(is_null($simpleItemStartCol)) $simpleItemStartCol=$col;
                          
                          
                          //$formatHelper->setValue($formatHelper->getFocusOn(),$qty);
                          $formatHelper->downMove($formatHelper->getFocusOn());
                          $formatHelper->moveToCol($formatHelper->getFocusOn(),$simpleItemStartCol);
                     }


                $timerZone++;
            }
            
            //设置自动列宽
            //$this->_autoWith($formatHelper->getExcel()->getActiveSheet(),range('A','Z'));
            $objWriter = PHPExcel_IOFactory::createWriter($formatHelper->getExcel(), "Excel2007");
            $objWriter->save($file);

            return $this->_targetUrl($filename);
       }else{
           throw new Exception("excel plugin load failed");
       }
    

    }
    
    public function _getColByTitle($label,$zone)
    {
        return $zone->getColByTitle($label);
    }
    
    protected function _autoWith($sheet,$cols)
    {
        
        foreach($cols as $col){
          $sheet->getColumnDimension($col)->setAutoSize(true);  
        }
    }

    protected function _generateFile($folderPath,$filename,$forceRebuild=false)
    {
        $filePath=$folderPath.DIRECTORY_SEPARATOR.$filename;
            
        if(file_exists($filename) and !$forceRebuild)
        {
            return $filePath;
        }
        
        if(!is_dir($folderPath))
        {
            if(!mkdir($folderPath)){
              throw new throwException("create {$this->_foldername()} failed");   
            }
        }

        $handle=fopen($filePath,'w+' );
        if(!$handle)
        {
            throw new Exception("create file $filename failed");
        }
        fclose($handle);
        return $filePath;
    }
    protected function _exportTo()
    {
        return Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA).DS.$this->_foldername();
    }
    protected function _targetUrl($filename)
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$this->_foldername()."/$filename";
    }
    protected function _foldername()
    {
        return 'martinexport';
    }
    
    protected function _productImgPath($imgUrl)
    {
        $product=Mage::getModel('catalog/product'); 
        /*  
        $gallery=$product->getMediaGalleryImages();
        foreach($gallery as $_image)
        {
            $imgUrl=(string)Mage::helper('catalog/image')->init($product, 'thumbnail', $_image->getFile())->resize($this->_imgSize());
            break;
        }
        */
        $imgUrl=Mage::helper('catalog/image')->init($product, 'thumbnail',$imgUrl)->resize(90);
        if($imgUrl)
        {
            $mediaUrl=Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
            $mediarDir=Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA);
            $imgPath=str_replace($mediaUrl, $mediarDir.DS, $imgUrl);
            $imgPath=str_replace('/',DS,$imgPath);
            return   $imgPath;
        }
    }
    protected function _imgSize()
    {
        return 100;
    }
    
    public function setExcelValue($cell,$value)
    {
        $formatHelper=$this->_formatHeler();
        $formatHelper->setValue($cell,$value);
    }
}

