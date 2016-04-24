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
class Rack_Jp_Core_Model_Tax_Quote_Shipping extends Mage_Tax_Model_Sales_Total_Quote_Shipping
{
    /**
     * Collect totals information about shipping
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_Sales_Model_Quote_Address_Total_Shipping
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        Mage_Sales_Model_Quote_Address_Total_Abstract::collect($address);
        $calc               = $this->_calculator;
        $store              = $address->getQuote()->getStore();
        $storeTaxRequest    = $calc->getRateOriginRequest($store);
        $addressTaxRequest  = $calc->getRateRequest(
            $address,
            $address->getQuote()->getBillingAddress(),
            $address->getQuote()->getCustomerTaxClassId(),
            $store
        );

        $shippingTaxClass = $this->_config->getShippingTaxClass($store);
        $storeTaxRequest->setProductClassId($shippingTaxClass);
        $addressTaxRequest->setProductClassId($shippingTaxClass);

        $priceIncludesTax = $this->_config->shippingPriceIncludesTax($store);
        if ($priceIncludesTax) {
            $this->_areTaxRequestsSimilar = $calc->compareRequests($addressTaxRequest, $storeTaxRequest);
        }

        $shipping           = $taxShipping = $address->getShippingAmount();
        $baseShipping       = $baseTaxShipping = $address->getBaseShippingAmount();
        $rate               = $calc->getRate($addressTaxRequest);
        if ($priceIncludesTax) {
            if ($this->_areTaxRequestsSimilar) {
                $tax            = $this->_round($calc->calcTaxAmount($shipping, $rate, true, false), $rate, true);
                $baseTax        = $this->_round($calc->calcTaxAmount($baseShipping, $rate, true, false), $rate, true, 'base');
                $taxShipping    = $shipping;
                $baseTaxShipping= $baseShipping;
                $shipping       = $shipping - $tax;
                $baseShipping   = $baseShipping - $baseTax;
                $taxable        = $taxShipping;
                $baseTaxable    = $baseTaxShipping;
                $isPriceInclTax = true;
            } else {
                $storeRate      = $calc->getStoreRate($addressTaxRequest, $store);
                $storeTax       = $calc->calcTaxAmount($shipping, $storeRate, true, false);
                $baseStoreTax   = $calc->calcTaxAmount($baseShipping, $storeRate, true, false);
                $shipping       = $calc->round($shipping - $storeTax);
                $baseShipping   = $calc->round($baseShipping - $baseStoreTax);
                $tax            = $this->_round($calc->calcTaxAmount($shipping, $rate, false, false), $rate, false);
                $baseTax        = $this->_round($calc->calcTaxAmount($baseShipping, $rate, false, false), $rate, false, 'base');
                $taxShipping    = $shipping + $tax;
                $baseTaxShipping= $baseShipping + $baseTax;
                $taxable        = $shipping;
                $baseTaxable    = $baseShipping;
                $isPriceInclTax = false;
            }
        } else {
            $tax            = $this->_round($calc->calcTaxAmount($shipping, $rate, false, false), $rate, false);
            $baseTax        = $this->_round($calc->calcTaxAmount($baseShipping, $rate, false, false), $rate, false, 'base');
            $taxShipping    = $shipping + $tax;
            $baseTaxShipping= $baseShipping + $baseTax;
            $taxable        = $shipping;
            $baseTaxable    = $baseShipping;
            $isPriceInclTax = false;
        }

        $current = $address->getQuote()->getQuoteCurrencyCode();
        $base = $address->getQuote()->getBaseCurrencyCode();
        $round = Mage::getStoreConfig('tax/calculation/round');

        if(Mage::helper('jpcore')->canRemoveDecimal($current)) {
            switch($round) {
                case 'ceil':
                    $shipping = ceil($shipping);
                    $taxShipping = ceil($taxShipping);
                    $taxable = ceil($taxable);
                    break;
                case 'floor':
                    $shipping = floor($shipping);
                    $taxShipping = floor($taxShipping);
                    $taxable = floor($taxable);
                    break;
            }
        }

        if(Mage::helper('jpcore')->canRemoveDecimal($base)) {
            switch($round) {
                case 'ceil':
                    $baseShipping = ceil($baseShipping);
                    $baseTaxShipping = ceil($baseTaxShipping);
                    $baseTaxable = ceil($baseTaxable);
                    break;
                case 'floor':
                    $baseShipping = floor($baseShipping);
                    $baseTaxShipping = floor($baseTaxShipping);
                    $baseTaxable = floor($baseTaxable);
                    break;
            }
        }

        $address->setTotalAmount('shipping', $shipping);
        $address->setBaseTotalAmount('shipping', $baseShipping);
        $address->setShippingInclTax($taxShipping);
        $address->setBaseShippingInclTax($baseTaxShipping);
        $address->setShippingTaxable($taxable);
        $address->setBaseShippingTaxable($baseTaxable);
        $address->setIsShippingInclTax($isPriceInclTax);
        if ($this->_config->discountTax($store)) {
            $address->setShippingAmountForDiscount($taxShipping);
            $address->setBaseShippingAmountForDiscount($baseTaxShipping);
        }
        return $this;
    }
}