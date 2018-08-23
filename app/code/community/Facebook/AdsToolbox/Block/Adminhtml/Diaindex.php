<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

if (file_exists(__DIR__.'/Feedindex.php')) {
  include_once 'Feedindex.php';
} else {
  include_once 'Facebook_AdsToolbox_Block_Adminhtml_Feedindex.php';
}

if (file_exists(__DIR__.'/Pixelindex.php')) {
  include_once 'Pixelindex.php';
} else {
  include_once 'Facebook_AdsToolbox_Block_Adminhtml_Pixelindex.php';
}

if (file_exists(__DIR__.'/../../lib/fb.php')) {
  include_once __DIR__.'/../../lib/fb.php';
} else {
  include_once 'Facebook_AdsToolbox_lib_fb.php';
}

class Facebook_AdsToolbox_Block_Adminhtml_Diaindex
  extends Mage_Adminhtml_Block_Template {

  private $pixelIndex = null;
  private $feedIndex = null;

  private function getPixelindex() {
    if ($this->pixelIndex == null) {
      $this->pixelIndex = new Facebook_AdsToolbox_Block_Adminhtml_Pixelindex();
    }
    return $this->pixelIndex;
  }

  private function getFeedindex() {
    if ($this->feedIndex == null) {
      $this->feedIndex = new Facebook_AdsToolbox_Block_Adminhtml_Feedindex();
    }
    return $this->feedIndex;
  }

  public function fetchPixelId() {
    return $this->getPixelindex()->fetchPixelId();
  }

  public function fetchStoreBaseCurrency() {
    return $this->getPixelindex()->fetchBaseCurrency();
  }

  public function fetchStoreName() {
    return htmlspecialchars(FacebookAdsToolbox::getStoreName(), ENT_QUOTES, 'UTF-8');
  }

  public function fetchStoreTimezone() {
    return $this->getPixelindex()->fetchTimezone();
  }

  public function getPixelAjaxRoute() {
    return $this->getPixelindex()->getAjaxRoute();
  }

  public function getStoreAjaxRoute() {
    return Mage::helper('adminhtml')->getUrl(
      'adminhtml/fbstore/ajax');
  }

  public function determineFbTimeZone($magentoTimezone) {
    return $this->getPixelindex()->determineFbTimeZone();
  }

  public function getStoreBaseUrl() {
    return FacebookAdsToolbox::getBaseUrl();
  }

  public function fetchFeedSetupEnabled() {
    return $this->getFeedindex()->fetchFeedSetupEnabled();
  }

  public function fetchFeedSetupFormat() {
    return $this->getFeedindex()->fetchFeedSetupFormat();
  }

  public function getFeedUrl() {
    return sprintf('%sfacebook_adstoolbox_product_feed.%s',
      $this->getFeedIndex()->getBaseUrl(),
      strtolower($this->fetchFeedSetupFormat())
    );
  }

  public function fetchFeedSamples() {
    $ob = Mage::getModel('Facebook_AdsToolbox/observer');
    $obins = new $ob;
    $productSamples = $obins->generateFacebookProductSamples();
    return $productSamples;
  }

  public function getDiaSettingId() {
    return Mage::getStoreConfig('facebook_ads_toolbox/dia/setting/id');
  }

  public function getDiaSettingIdAjaxRoute() {
    return Mage::helper('adminhtml')
      ->getUrl('adminhtml/fbmain/ajax');
  }

  public function getFeedGenerateNowAjaxRoute() {
    return $this->getFeedIndex()->getAjaxGenerateNowRoute();
  }

  public function enableFeedNOW() {
    $feed_setup = $this->getFeedIndex()->fetchFeedSetupEnabled();
    if (!$feed_setup) {
      Mage::getModel('core/config')->saveConfig(
        FacebookProductFeed::PATH_FACEBOOK_ADSTOOLBOX_FEED_GENERATION_ENABLED,
        true);
      return true;
    }
    return false;
  }

  public function getModuleList() {
    $modules = Mage::getConfig()->getNode('modules')->children();
    $simple_module_list = array();
    foreach ($modules as $moduleName => $moduleSettings) {
      $active = $moduleSettings->is('active') ? "active" : "disabled";
      $version_key = 'version';
      $module = $moduleName . ", " . $active . ", " . $moduleSettings->$version_key;
      array_push($simple_module_list, $module);
    }
    return implode('; ', $simple_module_list);
  }

  public function getStores() {
    $stores = Mage::app()->getStores(true);

    $store_map = array();
    foreach ($stores as $store) {
      $val = $store->getWebsite()->getName() . ' > ' .
        $store->getGroup()->getName()  . ' > ' .
        $store->getName();
      $store_map[$val] = $store->getId();
    }
    return $store_map;
  }

  public function getSelectedStore() {
      return FacebookAdsToolbox::getDefaultStoreId();
  }

  public function checkFeedWriteError() {
    return Mage::getModel('Facebook_AdsToolbox/observer')->checkFeedWriteError();
  }
}
