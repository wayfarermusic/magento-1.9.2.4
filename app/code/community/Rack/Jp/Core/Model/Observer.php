<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@principle-works.jp so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future. If you wish to customize it for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Localize
 * @package    Rack_Jp_Core
 * @copyright  Copyright (c) 2015 Veriteworks Inc. (http://principle-works.jp/)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Rack_Jp_Core_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * restore checkout method on admin
     *
     * @param $observer
     * @return $this
     */
    public function restoreCheckoutMethod($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        if (!$order->getCustomerId()) {
            $quote->setCheckoutMethod('guest');
        }

        return $this;
    }

    /**
     * restore account related info on admin
     *
     * @param $observer
     * @return $this
     */
    public function restoreAccountInfo($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();
        $oldOrder = Mage::getModel('sales/order')->load($order->getRelationParentId());

        if (!$order->getCustomerId()) {
            $billing = $order->getBillingAddress();
            if(!$order->getCustomerFirstnamekana() && ($billing->getFirstnamekana() != $oldOrder->getCustomerFirstnamekana())) {
                $order->setCustomerFirstnamekana($billing->getFirstnamekana());
            } else {
                $order->setCustomerFirstnamekana($oldOrder->getCustomerFirstnamekana());
            }
            if(!$order->getCustomerLastnamekana() && ($billing->getLastnamekana() != $oldOrder->getCustomerLastnamekana())) {
                $order->setCustomerLastnamekana($billing->getLastnamekana());
            } else {
                $order->setCustomerLastnamekana($oldOrder->getCustomerLastnamekana());
            }

            $order->setCustomerFirstname($billing->getFirstname());
            $order->setCustomerLastname($billing->getLastname());

            $order->setCustomerDob($oldOrder->getCustomerDob());
            $order->setCustomerGender($oldOrder->getCustomerGender());
            $order->setCustomerGroupId(0);
            $order->save();
        }


        return $this;
    }

    /**
     * rewrite tax class depends on magento version.
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function rewriteClasses(Varien_Event_Observer $observer)
    {
        $_edition = Mage::getEdition();
        $_version = Mage::getVersionInfo();

        if($_edition == Mage::EDITION_COMMUNITY) {
            switch($_version['minor']) {
                case '9':
                    Mage::getConfig()->setNode('global/models/tax/rewrite/sales_total_quote_shipping', 'Rack_Jp_Core_Model_Tax_Quote_Shipping19');
                    break;
                case '8':
                    switch($_version['revision']) {
                        case '0':
                            Mage::getConfig()->setNode('global/models/tax/rewrite/sales_total_quote_tax', 'Rack_Jp_Core_Model_Tax_Quote_Tax18');
                            break;
                        case '1':
                            Mage::getConfig()->setNode('global/models/tax/rewrite/sales_total_quote_tax', 'Rack_Jp_Core_Model_Tax_Quote_Tax181');
                            break;
                    }
                    break;
                case '7':
                    Mage::getConfig()->setNode('global/models/tax/rewrite/sales_total_quote_tax', 'Rack_Jp_Core_Model_Tax_Quote_Tax17');
                    break;
            }
        } else {
            switch($_version['minor']) {
                case '14':
                    Mage::getConfig()->setNode('global/models/tax/rewrite/sales_total_quote_shipping', 'Rack_Jp_Core_Model_Tax_Quote_Shipping19');
                    break;
                case '13':
                    switch($_version['revision']) {
                        case '0':
                            Mage::getConfig()->setNode('global/models/tax/rewrite/sales_total_quote_tax', 'Rack_Jp_Core_Model_Tax_Quote_Tax18');
                            break;
                        case '1':
                            Mage::getConfig()->setNode('global/models/tax/rewrite/sales_total_quote_tax', 'Rack_Jp_Core_Model_Tax_Quote_Tax181');
                            break;
                    }
                    break;
                case '12':
                    Mage::getConfig()->setNode('global/models/tax/rewrite/sales_total_quote_tax', 'Rack_Jp_Core_Model_Tax_Quote_Tax17');
                    break;
            }
        }
        return $this;
    }

}