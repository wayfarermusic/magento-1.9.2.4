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
class Rack_Jp_Core_Model_Locale extends Mage_Core_Model_Locale
{

    /**
     * Create Mage_Core_Model_Locale_Currency object for current locale
     *
     * @param   string $currency
     * @return  Mage_Core_Model_Locale_Currency
     */
    public function currency($currency)
    {
        Varien_Profiler::start('locale/currency');
        if (!isset(self::$_currencyCache[$this->getLocaleCode()][$currency])) {
            try {
                $currencyObject = new Zend_Currency($currency, $this->getLocale());
                if (Mage::helper('jpcore')->canRemoveDecimal($currency)) {
                    $options['precision'] = 0;
                    $currencyObject->setFormat(array('precision' => $options["precision"]));
                }
            } catch (Exception $e) {
                $currencyObject = new Zend_Currency($this->getCurrency(), $this->getLocale());
                $options = array(
                        'name'      => $currency,
                        'currency'  => $currency,
                        'symbol'    => $currency
                );
                $currencyObject->setFormat($options);

                $options = new Varien_Object($options);
                Mage::dispatchEvent('currency_display_options_forming', array(
                    'currency_options' => $options,
                    'base_code' => $currency
                ));
            }

            self::$_currencyCache[$this->getLocaleCode()][$currency] = $currencyObject;
        }
        Varien_Profiler::stop('locale/currency');
        return self::$_currencyCache[$this->getLocaleCode()][$currency];
    }

    /**
     * get js price format
     *
     * @return array
     */
    public function getJsPriceFormat()
    {
        // For JavaScript prices
        $parentFormat=parent::getJsPriceFormat();
        $options = array();

        if(Mage::helper('jpcore')->canRemoveDecimal(Mage::app()->getStore()->getCurrentCurrencyCode())) {
            $options['precision'] = 0;
        }

        if (array_key_exists('precision', $options))
        {
            $parentFormat["requiredPrecision"] = $options["precision"];
            $parentFormat["precision"] = $options["precision"];
        }

        return $parentFormat;
    }

//    /**
//     * Retrieve ISO datetime format
//     *
//     * @param   string $type
//     * @return  string
//     */
//    public function getDateTimeFormat($type)
//    {
//        return "M/d/yyyy H:mm";
//    }
}
