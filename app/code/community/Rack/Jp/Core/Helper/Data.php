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
class Rack_Jp_Core_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * options
     *
     * @var array
     */
    protected $_options = array();

    /**
     * no under decimal currencies
     *
     * @var array
     */
    protected $_currencies = array();

    /**
     * returns current store can use JPY or not.
     *
     * @return bool
     */
    public function canUseJpy()
    {
        $store = Mage::app()->getStore();
        if ($this->canRemoveDecimal($store->getCurrentCurrencyCode())) {
            $available = $store->getAvailableCurrencyCodes();

            if (in_array('JPY', $available)) {
                return true;
            }
        }
        return false;
    }

    public function canRemoveDecimal($currency)
    {
        if(count($this->_currencies) == 0) {
            $this->_currencies = explode(',', Mage::getStoreConfig('jpcore/currency/remove_decimal'));
        }

        foreach($this->_currencies as $allowed) {
            if($currency == $allowed)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * returns currency options array.
     *
     * @param array $options
     * @return array
     */
    public function getOptions($options = array()) {
        if (!$this->_options) {
            $store = Mage::app()->getStore();
            if ($store->getCurrentCurrencyCode() == 'JPY') {
                $position = Mage::getStoreConfig('jpcore/currency/position');
                if ($position ==  Zend_Currency::RIGHT) {
                    $this->_options['position'] = (int)$position;
                    $this->_options['symbol'] = $this->__('Yen');
                }

                if($this->_checkCurrentCurrency()) {
                    $this->_options['precision'] = 0;
                }

            }
        }
        return array_merge($options, $this->_options);
    }

    /**
     * check current currency depends on env.
     *
     * @return bool
     */
    protected function _checkCurrentCurrency()
    {
        $obj = null;
        if(Mage::registry('current_order')) {
            $obj = Mage::registry('current_order');
        } elseif(Mage::registry('current_invoice')){
            $obj = Mage::registry('current_invoice');
        } elseif(Mage::registry('current_creditmemo')) {
            $obj = Mage::registry('current_creditmemo');
        } elseif($quote = Mage::getSingleton('checkout/session')->getQuote()) {
            $obj = $quote;
        }

        if(is_null($obj)) {
            return true;
        }

        if(method_exists(get_class($obj), 'getOrderCurrencyCode') && $this->canRemoveDecimal($obj->getOrderCurrencyCode()))
        {
            return true;
        } elseif($this->canRemoveDecimal($obj->getQuoteCurrencyCode())) {
            return true;
        }

        return false;

    }
}
