<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */

if (file_exists(__DIR__.'/../../lib/fb.php')) {
  include_once __DIR__.'/../../lib/fb.php';
} else {
  include_once __DIR__.'/../../../../Facebook_AdsToolbox_lib_fb.php';
}

if (file_exists(__DIR__.'/../../Model/FacebookProductFeed.php')) {
  include_once __DIR__.'/../../Model/FacebookProductFeed.php';
} else if (file_exists(__DIR__.'/../../Facebook_AdsToolbox_Model_FacebookProductFeed.php')) {
  include_once __DIR__.'/../../Facebook_AdsToolbox_Model_FacebookProductFeed.php';
} else {
  include_once 'Facebook_AdsToolbox_Model_FacebookProductFeed.php';
}

class Facebook_AdsToolbox_Adminhtml_FbfeedlogController
  extends Mage_Adminhtml_Controller_Action {

  public static function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" ||
      (($temp = strlen($haystack) - strlen($needle)) >= 0 &&
        strpos($haystack, $needle, $temp) !== false);
  }

  private function ajaxSend($response) {
    $this->getResponse()->setHeader('Content-type', 'application/json');
    $this->getResponse()->setBody(
      Mage::helper('core')->jsonEncode($response));
  }

  public function ajaxAction() {
    $isAjax = $this->getRequest()->isAjax();
    if (!$isAjax) {
      $this->getResponse()->setRedirect(
        Mage::helper('adminhtml')->getUrl(
          'adminhtml/fbfeed/index'));
      return;
    }

    // in default. get request will return lastrunlogs
    $this->doQuerylastrunlogs($this->getRequest());
  }

  private function doQuerylastrunlogs($request) {
    $response = array(
      'success' => true,
    );
    $logfile = Mage::getBaseDir('log').'/'.FacebookProductFeed::LOGFILE;
    $fp = fopen($logfile, 'r');
    if (!$fp) {
      $response['lastrunlogs'] =
        'Read '.FacebookProductFeed::LOGFILE.' error!';
      $this->ajaxSend($response);
      return;
    }

    $pos = -1; // Skip final new line character (Set to -1 if not present)
    $lines = array();
    $currentLine = '';
    $found = false;
    while (-1 !== fseek($fp, $pos, SEEK_END)) {
      $char = fgetc($fp);
      if (PHP_EOL == $char) {
        $lines[] = $currentLine;
        if (self::endsWith(
          $currentLine,
          'feed generation start...')) {
          $found = true;
          break;
        }
        $currentLine = '';
      } else {
        $currentLine = $char . $currentLine;
      }
      $pos--;
    }
    if ($found) {
      $response['lastrunlogs'] = implode("\n", array_reverse($lines));
    } else {
      $response['lastrunlogs'] = 'Can not find last run logs!';
    }

    $this->ajaxSend($response);
  }
}
