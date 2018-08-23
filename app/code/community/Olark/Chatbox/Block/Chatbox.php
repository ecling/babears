<?php
/**
 * Widget that adds Olark Live Chat to Magento stores.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@olark.com so we can send you a copy immediately.
 *
 * @category    Olark
 * @package     Olark_Chatbox
 * @copyright   Copyright 2012. Habla, Inc. (http://www.olark.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Olark_Chatbox_Block_Chatbox
    extends Mage_Core_Block_Abstract
    implements Mage_Widget_Block_Interface
{

    /**
     * Returns the version of this Olark CartSaver plugin.
     *
     * @return string
    */
    protected function _getVersion() {
        // http://www.magentron.com/blog/2011/07/20/how-to-display-your-extension-version-in-magento-admin
        return Mage::getConfig()->getNode()->modules->Olark_Chatbox->version;
    }

    /**
     * Pops the list of recent events.  This empties the olark_chat_events list
     * after returning the recent events.
     *
     * @return array
    */
    protected function _popRecentEvents() {
        // Passing `true` to getData() causes that data key to be cleared.
        $session = Mage::getSingleton('core/session');
        return $session->getData('olark_chatbox_events', true);
    }

    /**
     * Produces Olark Chatbox html
     *
     * @return string
     */
    protected function _toHtml()
    {
        return true;
        function formatPrice($value) {
            return Mage::helper('core')->currency(
                $value,
                true, // Format the value for the localized currency.
                false // Do not HTMLize the value.
            );
        }

        $customer = array();
        $products = array();
        $totalValueOfItems = 0;

        // Don't show the Olark code at all if there is no Site ID.
        $siteID = $this->getData('siteID');
        if (empty($siteID)) {
            return '';
        }

        // Capture customer information for Olark config.
        $info = Mage::getSingleton('customer/session')->getCustomer();
        if ($info) {

            $billingAddress = $info->getPrimaryBillingAddress();
            if ($billingAddress) {
                $billingAddress = $billingAddress->format('text');
            }

            $shippingAddress = $info->getPrimaryShippingAddress();
            if ($shippingAddress) {
                $shippingAddress = $shippingAddress->format('text');
            }

            $customer = array(
                'name' => $info->getName(),
                'email' => $info->getEmail(),
                'billing_address' => $billingAddress,
                'shipping_address' => $shippingAddress
            );

        }

        // Capture cart information for Olark config.
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $items = $quote->getAllVisibleItems();
        if ($items) {
            $totalValueOfItems = 0;
            foreach ($items as $item) {

                // Capture the raw item in case we want to show
                // more information in the future without updating
                // our Magento app.
                $magentoItem = $item->getData();
                $magentoItem['formatted_price'] = formatPrice($item->getPrice());

                $product = array(
                    'name' => $item->getName(),
                    'sku' => $item->getSku(),
                    'quantity' => $item->getQty(),
                    'price' => $item->getPrice(),
                    'magento' => $magentoItem
                );
                $products[] = $product;
                $totalValueOfItems = $totalValueOfItems + ($product['price'] * $product['quantity']);
            }
        }

        // Attempt to get totals from Magento directly.
        $totals = $quote->getTotals();
        foreach ($totals as $total) {
            $name = $total->getCode();
            $extraItems[] = array(
                'name' => $name,
                'price' => $total->getValue(),
                'formatted_price' => formatPrice($total->getValue())
            );
            if ('subtotal' == $name)  {
                $totalValueOfItems = $total->getValue();
            }
        }

        // Capture extra Magento-specific data for Olark config.
        $recentEvents = $this->_popRecentEvents();
        $magentoData = array(
            'total' => $totalValueOfItems,
            'formatted_total' => formatPrice($totalValueOfItems),
            'extra_items' => $extraItems,
            'recent_events' => $recentEvents 
        );

        // Build and return HTML that represents the Olark embed and
        // CartSaver-specific configuration.
        $html = '
        <!-- begin olark code -->
        <script type="text/javascript">
          window.olark||(function(c){var f=window,d=document,l=f.location.protocol=="https:"?"https:":"http:",z=c.name,r="load";var nt=function(){f[z]=function(){(a.s=a.s||[]).push(arguments)};var a=f[z]._={},q=c.methods.length;while(q--){(function(n){f[z][n]=function(){f[z]("call",n,arguments)}})(c.methods[q])}a.l=c.loader;a.i=nt;a.p={0:+new Date};a.P=function(u){a.p[u]=new Date-a.p[0]};function s(){a.P(r);f[z](r)}f.addEventListener?f.addEventListener(r,s,false):f.attachEvent("on"+r,s);var ld=function(){function p(hd){hd="head";return["<",hd,"></",hd,"><",i,\' onl\' + \'oad="var d=\',g,";d.getElementsByTagName(\'head\')[0].",j,"(d.",h,"(\'script\')).",k,"=\'",l,"//",a.l,"\'",\'"\',"></",i,">"].join("")}var i="body",m=d[i];if(!m){return setTimeout(ld,100)}a.P(1);var j="appendChild",h="createElement",k="src",n=d[h]("div"),v=n[j](d[h](z)),b=d[h]("iframe"),g="document",e="domain",o;n.style.display="none";m.insertBefore(n,m.firstChild).id=z;b.frameBorder="0";b.id=z+"-loader";if(/MSIE[ ]+6/.test(navigator.userAgent)){b.src="javascript:false"}b.allowTransparency="true";v[j](b);try{b.contentWindow[g].open()}catch(w){c[e]=d[e];o="javascript:var d="+g+".open();d.domain=\'"+d.domain+"\';";b[k]=o+"void(0);"}try{var t=b.contentWindow[g];t.write(p());t.close()}catch(x){b[k]=o+\'d.write("\'+p().replace(/"/g,String.fromCharCode(92)+\'"\')+\'");d.close();\'}a.P(2)};ld()};nt()})({
          loader: "static.olark.com/jsclient/loader0.js",name:"olark",methods:["configure","extend","declare","identify"]});
        </script>
        <noscript><a href="https://www.olark.com/site/'.$siteID.'/contact" title="Contact us" target="_blank">Questions? Feedback?</a> powered by <a href="http://www.olark.com?welcome" title="Olark live chat software">Olark live chat software</a></noscript>
        <!-- olark magento cart saver -->
        <script type="text/javascript">
          olark.extend("CartSaver");
          olark.configure("CartSaver.version", "Magento@'.$this->_getVersion().'");
          olark.configure("CartSaver.customer", '.json_encode($customer).');
          olark.configure("CartSaver.items", '.json_encode($products).');
          olark.configure("CartSaver.magento", '.json_encode($magentoData).');
        </script>
        <!-- custom olark config -->
        '.$this->getData('customConfig').'
        <script>
          olark.identify("'.$siteID.'");
        </script>
        ';

        return $html;
    }
}
