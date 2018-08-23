<?php
/*
* Name Extension: Tm_ImageZoom
*/

class Tm_ImageZoom_Block_Zoom extends Mage_Core_Block_Abstract
{

    const XML_PATH_ENABLED = 'imagezoom/general/active';
    const XML_PATH_VARIANT = 'imagezoom/general/zoom_variant';

    public function isEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ENABLED, $store);
    }

    public function imageZoomVariant($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_VARIANT, $store);
    }

    public function _prepareLayout()
    {
                      
        if ($this->isEnabled()){

            $layout = $this->getLayout();
            $head = $layout->getBlock('head');

            if($this->imageZoomVariant() == 'unitegallery'){
                $media = $layout->getBlock('product.info.media');
                $media->setTemplate('tm/imagezoom/unitgallery_media.phtml');

                $head->addItem('skin_js', 'js/tm/imagezoom/unitegallery/unitegallery.js');
                $head->addItem('skin_js', 'js/tm/imagezoom/unitegallery/ug-theme-compact.js');
                $head->addItem('skin_js', 'js/tm/imagezoom/unitegallery/unitegallery.init.js');
                $head->addItem('skin_css', 'css/tm/imagezoom/unitegallery/unite-gallery.css');
                $head->addItem('skin_css', 'css/tm/imagezoom/unitegallery/skins/alexis.css');
            }
        } else return;
        
    }
    
}