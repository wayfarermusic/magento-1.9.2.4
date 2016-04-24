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
class Rack_Jp_Core_Model_Currency extends Mage_Directory_Model_Currency
{
    /**
     * format precision
     *
     * @param float $price
     * @param int $precision
     * @param array $options
     * @param bool $includeContainer
     * @param bool $addBrackets
     * @return string
     */
    public function formatPrecision($price, $precision, $options=array(), $includeContainer = true, $addBrackets = false)
    {
        $helper = Mage::helper('jpcore');
        if(!$helper->canRemoveDecimal($this->getCode())) {
            return parent::formatPrecision($price, $precision, $options, $includeContainer, $addBrackets);
        }

        if (!isset($options['precision'])) {
            $options['precision'] = $precision;
        }

        $store = Mage::app()->getStore();
        $base = $store->getBaseCurrencyCode();
        $current = $store->getCurrentCurrencyCode();

        if ($base === 'JPY' && $this->getCode() == $base) {
            if (Mage::getStoreConfig('jpcore/currency/position') ==  Zend_Currency::RIGHT) {
                $options['position'] = (int)Mage::getStoreConfig('jpcore/currency/position');
                $options['symbol'] = $helper->__('Yen');
            }
            $options['precision'] = 0;
        } elseif($current === 'JPY' && $this->getCode() === $current) {
            if (Mage::getStoreConfig('jpcore/currency/position') ==  Zend_Currency::RIGHT) {
                $options['position'] = (int)Mage::getStoreConfig('jpcore/currency/position');
                $options['symbol'] = $helper->__('Yen');
            }
            $options['precision'] = 0;
        } elseif($helper->canRemoveDecimal($current)) {
            $options['precision'] = 0;
        }
        if ($includeContainer) {
            return '<span class="price">' . ($addBrackets ? '[' : '') . $this->formatTxt($price, $options) . ($addBrackets ? ']' : '') . '</span>';
        }

        return $this->formatTxt($price, $options);
    }

    /**
     * format price text
     *
     * @param float $price
     * @param array $options
     * @return string
     */
    public function formatTxt($price, $options = array()) {
        $store = Mage::app()->getStore();
        $base = $store->getBaseCurrencyCode();
        $current = $store->getCurrentCurrencyCode();
        $helper = Mage::helper('jpcore');

        if($this->getCode() != 'JPY') {
            return parent::formatTxt($price, $options);
        }

        if ($base === 'JPY' && $this->getCode() == $base) {
            $options['precision'] = 0;
            if (Mage::getStoreConfig('jpcore/currency/position') ==  Zend_Currency::RIGHT) {
                $options['position'] = (int)Mage::getStoreConfig('jpcore/currency/position');
                $options['symbol'] = $helper->__('Yen');
            }
        } elseif ($current === 'JPY' && $this->getCode() === $current) {
            $options['precision'] = 0;
            if (Mage::getStoreConfig('jpcore/currency/position') ==  Zend_Currency::RIGHT) {
                $options['position'] = (int)Mage::getStoreConfig('jpcore/currency/position');
                $options['symbol'] = $helper->__('Yen');
            }
        }
        return parent::formatTxt($price, $options);
    }

    /**
     * format price
     *
     * @param float $price
     * @param array $options
     * @param bool $includeContainer
     * @param bool $addBrackets
     * @return string
     */
    public function format($price, $options = array(), $includeContainer = true, $addBrackets = false)
    {
        $store = Mage::app()->getStore();
        $base = $store->getBaseCurrencyCode();
        $current = $store->getCurrentCurrencyCode();
        $helper = Mage::helper('jpcore');
        $precision = 2;

        if(!$helper->canRemoveDecimal($this->getCode())) {
            return $this->formatPrecision($price, $precision, $options, $includeContainer, $addBrackets);
        }

        if ($helper->canRemoveDecimal($base) && $this->getCode() == $base) {
            $precision = 0;
        } elseif($helper->canRemoveDecimal($current) && $this->getCode() === $current) {
            $precision = 0;
        }

        return $this->formatPrecision($price, $precision, $options, $includeContainer, $addBrackets);
    }
}
