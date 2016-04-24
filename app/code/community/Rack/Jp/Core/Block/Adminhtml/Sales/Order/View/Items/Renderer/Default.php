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
class Rack_Jp_Core_Block_Adminhtml_Sales_Order_View_Items_Renderer_Default extends Mage_Adminhtml_Block_Sales_Order_View_Items_Renderer_Default
{
    /**
     * Display base and regular prices with specified rounding precision
     *
     * @param   float $basePrice
     * @param   float $price
     * @param   int $precision
     * @param   bool $strong
     * @param   string $separator
     * @return  string
     */
    public function displayRoundedPrices($basePrice, $price, $precision=2, $strong = false, $separator = '<br />')
    {
        if ($this->getOrder()->isCurrencyDifferent()) {
            $res = '';

            if(!Mage::registry('order_base_currency')) {
                Mage::register('order_base_currency', $this->getOrder()->getBaseCurrencyCode());
            }

            if(!Mage::registry('order_currency')) {
                Mage::register('order_currency', $this->getOrder()->getOrderCurrencyCode());
            }
            $helper = Mage::helper('jpcore');
            if($helper->canRemoveDecimal($this->getOrder()->getBaseCurrencyCode())) {
                $res.= $this->getOrder()->formatBasePricePrecision($basePrice, $precision);
            } else {
                $res.= $this->getOrder()->formatBasePricePrecision($basePrice, 0);
            }
            $res.= $separator;
            if($helper->canRemoveDecimal($this->getOrder()->getOrderCurrencyCode())) {
                $res.= $this->getOrder()->formatPricePrecision($price, $precision, true);
            } else {
                $res.= $this->getOrder()->formatPricePrecision($price, 0, true);
            }
        }
        else {
            $res = $this->getOrder()->formatPricePrecision($price, $precision);
            if ($strong) {
                $res = '<strong>'.$res.'</strong>';
            }
        }
        return $res;
    }

    /**
     * Retrieve include tax html formated content
     *
     * @param Varien_Object $item
     * @return string
     */
    public function displayPriceInclTax(Varien_Object $item)
    {
        $qty = ($item->getQtyOrdered() ? $item->getQtyOrdered() : ($item->getQty() ? $item->getQty() : 1));
        $baseTax = ($item->getTaxBeforeDiscount() ? $item->getTaxBeforeDiscount() : ($item->getTaxAmount() ? $item->getTaxAmount() : 0));
        $tax = ($item->getBaseTaxBeforeDiscount() ? $item->getBaseTaxBeforeDiscount() : ($item->getBaseTaxAmount() ? $item->getBaseTaxAmount() : 0));

        $basePriceTax = 0;
        $priceTax = 0;

        if (floatval($qty)) {
            $basePriceTax = $item->getBasePrice()+$baseTax/$qty;
            $priceTax = $item->getPrice()+$tax/$qty;
        }

        return $this->displayPrices(
            $this->getOrder()->getStore()->roundPrice($basePriceTax),
            $this->getOrder()->getStore()->roundPrice($priceTax)
        );
    }
}