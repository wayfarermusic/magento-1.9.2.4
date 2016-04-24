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
class Rack_Jp_Core_Model_Tax_Quote_Tax181 extends Mage_Tax_Model_Sales_Total_Quote_Tax
{
    /**
     * Calculate address total tax based on address subtotal
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @param   Varien_Object $taxRateRequest
     * @return  Mage_Tax_Model_Sales_Total_Quote
     */
    protected function _totalBaseCalculation(Mage_Sales_Model_Quote_Address $address, $taxRateRequest)
    {
        $items          = $this->_getAddressItems($address);
        $store          = $address->getQuote()->getStore();
        $taxGroups      = array();
        $itemTaxGroups  = array();

        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                foreach ($item->getChildren() as $child) {
                    $taxRateRequest->setProductClassId($child->getProduct()->getTaxClassId());
                    $rate = $this->_calculator->getRate($taxRateRequest);
                    $applied_rates = $this->_calculator->getAppliedRates($taxRateRequest);
                    $taxGroups[(string)$rate]['applied_rates'] = $applied_rates;
                    $taxGroups[(string)$rate]['incl_tax'] = $child->getIsPriceInclTax();
                    $this->_aggregateTaxPerRate($child, $rate, $taxGroups);
                    if ($rate > 0) {
                        $itemTaxGroups[$child->getId()] = $applied_rates;
                    }
                }
                $this->_recalculateParent($item);
            } else {
                $taxRateRequest->setProductClassId($item->getProduct()->getTaxClassId());
                $rate = $this->_calculator->getRate($taxRateRequest);
                $applied_rates = $this->_calculator->getAppliedRates($taxRateRequest);
                $taxGroups[(string)$rate]['applied_rates'] = $applied_rates;
                $taxGroups[(string)$rate]['incl_tax'] = $item->getIsPriceInclTax();
                $this->_aggregateTaxPerRate($item, $rate, $taxGroups);
                if ($rate > 0) {
                    $itemTaxGroups[$item->getId()] = $applied_rates;
                }
            }
        }

        if ($address->getQuote()->getTaxesForItems()) {
            $itemTaxGroups += $address->getQuote()->getTaxesForItems();
        }
        $address->getQuote()->setTaxesForItems($itemTaxGroups);

        foreach ($taxGroups as $rateKey => $data) {
            $rate = (float) $rateKey;
            $inclTax = $data['incl_tax'];

            $totalTax = $this->_calculator->calcTaxAmount(array_sum($data['totals']), $rate, $inclTax, false);
            $totalTax += array_sum($data['weee_tax']);
            $baseTotalTax = $this->_calculator->calcTaxAmount(array_sum($data['base_totals']), $rate, $inclTax, false);
            $baseTotalTax += array_sum($data['base_weee_tax']);

            $method = Mage::getStoreConfig('tax/calculation/round');
            $currentCurrency = $item->getQuote()->getQuoteCurrencyCode();
            $baseCurrency = $item->getQuote()->getBaseCurrencyCode();

            if(Mage::helper('jpcore')->canRemoveDecimal($currentCurrency)) {
                $totalTax = $method($totalTax);
            }

            if(Mage::helper('jpcore')->canRemoveDecimal($baseCurrency)) {
                $baseTotalTax = $method($baseTotalTax);
            }

            $this->_addAmount($totalTax);
            $this->_addBaseAmount($baseTotalTax);
            $totalTaxRounded = $this->_calculator->round($totalTax);
            $baseTotalTaxRounded = $this->_calculator->round($totalTaxRounded);
            $this->_saveAppliedTaxes($address, $data['applied_rates'], $totalTaxRounded, $baseTotalTaxRounded, $rate);
        }
        return $this;
    }

    /**
     * Aggregate row totals per tax rate in array
     *
     * @param   Mage_Sales_Model_Quote_Item_Abstract $item
     * @param   float $rate
     * @param   array $taxGroups
     * @return  Mage_Tax_Model_Sales_Total_Quote
     */
    protected function _aggregateTaxPerRate(
        $item, $rate, &$taxGroups, $taxId = null, $recalculateRowTotalInclTax = false
    )
    {
        $inclTax = $item->getIsPriceInclTax();
        $rateKey = ($taxId == null) ? (string)$rate : $taxId;
        $taxSubtotal = $subtotal = $item->getTaxableAmount();
        $baseTaxSubtotal = $baseSubtotal = $item->getBaseTaxableAmount();

        $isWeeeEnabled = $this->_weeeHelper->isEnabled();
        $isWeeeTaxable = $this->_weeeHelper->isTaxable();

        if (!isset($taxGroups[$rateKey]['totals'])) {
            $taxGroups[$rateKey]['totals'] = array();
            $taxGroups[$rateKey]['base_totals'] = array();
            $taxGroups[$rateKey]['weee_tax'] = array();
            $taxGroups[$rateKey]['base_weee_tax'] = array();
        }

        $hiddenTax = null;
        $baseHiddenTax = null;
        $weeeTax = null;
        $baseWeeeTax = null;
        $discount = 0;
        $rowTaxBeforeDiscount = 0;
        $baseRowTaxBeforeDiscount = 0;
        $weeeRowTaxBeforeDiscount = 0;
        $baseWeeeRowTaxBeforeDiscount = 0;


        switch ($this->_helper->getCalculationSequence($this->_store)) {
            case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_EXCL:
            case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_INCL:
                $rowTaxBeforeDiscount = $this->_calculator->calcTaxAmount($subtotal, $rate, $inclTax, false);
                $baseRowTaxBeforeDiscount = $this->_calculator->calcTaxAmount($baseSubtotal, $rate, $inclTax, false);

                if ($isWeeeEnabled && $isWeeeTaxable) {
                    $weeeRowTaxBeforeDiscount = $this->_calculateRowWeeeTax(0, $item, $rate, false);
                    $baseWeeeRowTaxBeforeDiscount = $this->_calculateRowWeeeTax(0, $item, $rate);
                    $rowTaxBeforeDiscount += $weeeRowTaxBeforeDiscount;
                    $baseRowTaxBeforeDiscount += $baseWeeeRowTaxBeforeDiscount;
                    $taxGroups[$rateKey]['weee_tax'][] = $this->_deltaRound($weeeRowTaxBeforeDiscount,
                        $rateKey, $inclTax);
                    $taxGroups[$rateKey]['base_weee_tax'][] = $this->_deltaRound($baseWeeeRowTaxBeforeDiscount,
                        $rateKey, $inclTax);
                }
                $taxBeforeDiscountRounded = $rowTax = $this->_deltaRound($rowTaxBeforeDiscount, $rateKey, $inclTax);
                $baseTaxBeforeDiscountRounded = $baseRowTax = $this->_deltaRound($baseRowTaxBeforeDiscount,
                    $rateKey, $inclTax, 'base');
                $item->setTaxAmount($item->getTaxAmount() + max(0, $rowTax));
                $item->setBaseTaxAmount($item->getBaseTaxAmount() + max(0, $baseRowTax));
                break;
            case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_EXCL:
            case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_INCL:
                if ($this->_helper->applyTaxOnOriginalPrice($this->_store)) {
                    $discount = $item->getOriginalDiscountAmount();
                    $baseDiscount = $item->getBaseOriginalDiscountAmount();
                } else {
                    $discount = $item->getDiscountAmount();
                    $baseDiscount = $item->getBaseDiscountAmount();
                }

                //We remove weee discount from discount if weee is not taxed
                if ($isWeeeEnabled && $this->_weeeHelper->includeInSubtotal()) {
                    $discount = $discount - $item->getWeeeDiscount();
                    $baseDiscount = $baseDiscount - $item->getBaseWeeeDiscount();
                }
                $taxSubtotal = max($subtotal - $discount, 0);
                $baseTaxSubtotal = max($baseSubtotal - $baseDiscount, 0);

                $rowTax = $this->_calculator->calcTaxAmount($taxSubtotal, $rate, $inclTax, false);
                $baseRowTax = $this->_calculator->calcTaxAmount($baseTaxSubtotal, $rate, $inclTax, false);

                $method = Mage::getStoreConfig('tax/calculation/round');
                $currentCurrency = $item->getQuote()->getQuoteCurrencyCode();
                $baseCurrency = $item->getQuote()->getBaseCurrencyCode();

                if(Mage::helper('jpcore')->canRemoveDecimal($currentCurrency)) {
                    $rowTax = $method($rowTax);
                }

                if(Mage::helper('jpcore')->canRemoveDecimal($baseCurrency)) {
                    $baseRowTax = $method($baseRowTax);
                }

                if ($isWeeeEnabled && $this->_weeeHelper->isTaxable()) {
                    $weeeTax = $this->_calculateRowWeeeTax($item->getWeeeDiscount(), $item, $rate, false);
                    $rowTax += $weeeTax;
                    $baseWeeeTax = $this->_calculateRowWeeeTax($item->getBaseWeeeDiscount(), $item, $rate);
                    $baseRowTax += $baseWeeeTax;
                    $taxGroups[$rateKey]['weee_tax'][] = $weeeTax;
                    $taxGroups[$rateKey]['base_weee_tax'][] = $baseWeeeTax;
                }

                $rowTax = $this->_deltaRound($rowTax, $rateKey, $inclTax);
                $baseRowTax = $this->_deltaRound($baseRowTax, $rateKey, $inclTax, 'base');

                $item->setTaxAmount($rowTax);
                $item->setBaseTaxAmount($baseRowTax);

                //Calculate the Row taxes before discount
                $rowTaxBeforeDiscount = $this->_calculator->calcTaxAmount(
                    $subtotal,
                    $rate,
                    $inclTax,
                    false
                );
                $baseRowTaxBeforeDiscount = $this->_calculator->calcTaxAmount(
                    $baseSubtotal,
                    $rate,
                    $inclTax,
                    false
                );


                if ($isWeeeTaxable) {
                    $weeeRowTaxBeforeDiscount = $this->_calculateRowWeeeTax(0, $item, $rate, false);
                    $rowTaxBeforeDiscount += $weeeRowTaxBeforeDiscount;
                    $baseWeeeRowTaxBeforeDiscount = $this->_calculateRowWeeeTax(0, $item, $rate);
                    $baseRowTaxBeforeDiscount += $baseWeeeRowTaxBeforeDiscount;
                }

                $taxBeforeDiscountRounded = max(
                    0,
                    $this->_deltaRound($rowTaxBeforeDiscount, $rateKey, $inclTax, 'tax_before_discount')
                );
                $baseTaxBeforeDiscountRounded = max(
                    0,
                    $this->_deltaRound($baseRowTaxBeforeDiscount, $rateKey, $inclTax, 'tax_before_discount_base')
                );

                if (!$item->getNoDiscount()) {
                    if ($item->getWeeeTaxApplied()) {
                        $item->setDiscountTaxCompensation($item->getDiscountTaxCompensation() +
                            $taxBeforeDiscountRounded - max(0, $rowTax));
                    }
                }

                if ($inclTax && $discount > 0) {
                    $roundedHiddenTax = $taxBeforeDiscountRounded - max(0, $rowTax);
                    $baseRoundedHiddenTax = $baseTaxBeforeDiscountRounded - max(0, $baseRowTax);
                    $this->_hiddenTaxes[] = array(
                        'rate_key' => $rateKey,
                        'qty' => 1,
                        'item' => $item,
                        'value' => $roundedHiddenTax,
                        'base_value' => $baseRoundedHiddenTax,
                        'incl_tax' => $inclTax,
                    );
                }
                break;
        }

        $rowTotalInclTax = $item->getRowTotalInclTax();
        if (!isset($rowTotalInclTax) || $recalculateRowTotalInclTax) {
            if ($this->_config->priceIncludesTax($this->_store)) {
                $item->setRowTotalInclTax($subtotal);
                $item->setBaseRowTotalInclTax($baseSubtotal);
            } else {
                $item->setRowTotalInclTax(
                    $item->getRowTotalInclTax() + $taxBeforeDiscountRounded - $weeeRowTaxBeforeDiscount);
                $item->setBaseRowTotalInclTax(
                    $item->getBaseRowTotalInclTax()
                    + $baseTaxBeforeDiscountRounded
                    - $baseWeeeRowTaxBeforeDiscount);
            }
        }

        $baseCurrency = $item->getQuote()->getBaseCurrencyCode();
        $currentCurrency = $item->getQuote()->getQuoteCurrencyCode();
        $method = Mage::getStoreConfig('tax/calculation/round');

        if(Mage::helper('jpcore')->canRemoveDecimal($baseCurrency)) {
            switch($method) {
                case 'ceil':
                    $taxGroups[$rateKey]['base_totals'][] = max(0, ceil($baseTaxSubtotal));
                    $taxGroups[$rateKey]['base_tax'][] = max(0, ceil($baseRowTax));
                    break;
                case 'floor':
                    $taxGroups[$rateKey]['base_totals'][] = max(0, floor($baseTaxSubtotal));
                    $taxGroups[$rateKey]['base_tax'][] = max(0, floor($baseRowTax));
                    break;
                case 'round':
                    $taxGroups[$rateKey]['base_totals'][] = max(0, $this->_calculator->round($baseTaxSubtotal));
                    $taxGroups[$rateKey]['base_tax'][] = max(0, $this->_calculator->round($baseRowTax));
                    break;
            }
        } else {
            $taxGroups[$rateKey]['base_totals'][] = max(0, ($baseTaxSubtotal));
            $taxGroups[$rateKey]['base_tax'][] = max(0, ($baseRowTax));
        }

        if(Mage::helper('jpcore')->canRemoveDecimal($currentCurrency)) {
            switch($method) {
                case 'ceil':
                    $taxGroups[$rateKey]['totals'][] = max(0, ceil($taxSubtotal));
                    $taxGroups[$rateKey]['tax'][] = max(0, ceil($rowTax));
                    break;
                case 'floor':
                    $taxGroups[$rateKey]['totals'][] = max(0, floor($taxSubtotal));
                    $taxGroups[$rateKey]['tax'][] = max(0, floor($rowTax));
                    break;
                case 'round':
                    $taxGroups[$rateKey]['totals'][] = max(0, $this->_calculator->round($taxSubtotal));
                    $taxGroups[$rateKey]['tax'][] = max(0, $this->_calculator->round($rowTax));
                    break;
            }
        } else {
            $taxGroups[$rateKey]['totals'][] = max(0, $taxSubtotal);
            $taxGroups[$rateKey]['tax'][] = max(0, $rowTax);
        }



        return $this;
    }

    /**
     * Calculate unit tax anount based on unit price
     *
     * @param   Mage_Sales_Model_Quote_Item_Abstract $item
     * @param   float $rate
     * @param   array $taxGroups
     * @param   string $taxId
     * @param   boolean $recalculateRowTotalInclTax
     * @return  Mage_Tax_Model_Sales_Total_Quote
     */
    protected function _calcUnitTaxAmount(
        $item, $rate, &$taxGroups = null, $taxId = null, $recalculateRowTotalInclTax = false
    )
    {
        $qty = $item->getTotalQty();
        $inclTax = $item->getIsPriceInclTax();
        $price = $item->getTaxableAmount();
        $basePrice = $item->getBaseTaxableAmount();
        $rateKey = ($taxId == null) ? (string)$rate : $taxId;

        $isWeeeEnabled = $this->_weeeHelper->isEnabled();
        $isWeeeTaxable = $this->_weeeHelper->isTaxable();

        $hiddenTax = null;
        $baseHiddenTax = null;
        $weeeTax = null;
        $baseWeeeTax = null;
        $unitTaxBeforeDiscount = null;
        $weeeTaxBeforeDiscount = null;
        $baseUnitTaxBeforeDiscount = null;
        $baseWeeeTaxBeforeDiscount = null;

        $baseCurrency = $item->getQuote()->getBaseCurrencyCode();
        $currentCurrency = $item->getQuote()->getQuoteCurrencyCode();
        $method = Mage::getStoreConfig('tax/calculation/round');

        switch ($this->_config->getCalculationSequence($this->_store)) {
            case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_EXCL:
            case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_INCL:
                $unitTaxBeforeDiscount = $this->_calculator->calcTaxAmount($price, $rate, $inclTax, false);
                $baseUnitTaxBeforeDiscount = $this->_calculator->calcTaxAmount($basePrice, $rate, $inclTax, false);

                if ($isWeeeEnabled && $isWeeeTaxable) {
                    $weeeTaxBeforeDiscount = $this->_calculateWeeeTax(0, $item, $rate, false);
                    $unitTaxBeforeDiscount += $weeeTaxBeforeDiscount;
                    $baseWeeeTaxBeforeDiscount = $this->_calculateWeeeTax(0, $item, $rate);
                    $baseUnitTaxBeforeDiscount += $baseWeeeTaxBeforeDiscount;
                }
                $unitTaxBeforeDiscount = $unitTax = $this->_calculator->round($unitTaxBeforeDiscount);
                $baseUnitTaxBeforeDiscount = $baseUnitTax = $this->_calculator->round($baseUnitTaxBeforeDiscount);
                break;
            case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_EXCL:
            case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_INCL:
                $discountAmount = $item->getDiscountAmount() / $qty;
                $baseDiscountAmount = $item->getBaseDiscountAmount() / $qty;

                //We want to remove weee
                if ($isWeeeEnabled) {
                    $discountAmount = $discountAmount - $item->getWeeeDiscount() / $qty;
                    $baseDiscountAmount = $baseDiscountAmount - $item->getBaseWeeeDiscount() / $qty;
                }

                $unitTaxBeforeDiscount = $this->_calculator->calcTaxAmount($price, $rate, $inclTax, false);
                $unitTaxDiscount = $this->_calculator->calcTaxAmount($discountAmount, $rate, $inclTax, false);
                $unitTax = $this->_calculator->round(max($unitTaxBeforeDiscount - $unitTaxDiscount, 0));

                $baseUnitTaxBeforeDiscount = $this->_calculator->calcTaxAmount($basePrice, $rate, $inclTax, false);
                $baseUnitTaxDiscount = $this->_calculator->calcTaxAmount($baseDiscountAmount, $rate, $inclTax, false);
                $baseUnitTax = $this->_calculator->round(max($baseUnitTaxBeforeDiscount - $baseUnitTaxDiscount, 0));

                if ($isWeeeEnabled && $this->_weeeHelper->isTaxable()) {
                    $weeeTax = $this->_calculateRowWeeeTax($item->getWeeeDiscount(), $item, $rate, false);
                    $weeeTax = $weeeTax / $qty;
                    $unitTax += $weeeTax;
                    $baseWeeeTax = $this->_calculateRowWeeeTax($item->getBaseWeeeDiscount(), $item, $rate);
                    $baseWeeeTax = $baseWeeeTax / $qty;
                    $baseUnitTax += $baseWeeeTax;
                }

                $unitTax = $this->_calculator->round($unitTax);
                $baseUnitTax = $this->_calculator->round($baseUnitTax);

                //Calculate the weee taxes before discount
                $weeeTaxBeforeDiscount = 0;
                $baseWeeeTaxBeforeDiscount = 0;

                if ($isWeeeTaxable) {
                    $weeeTaxBeforeDiscount = $this->_calculateWeeeTax(0, $item, $rate, false);
                    $unitTaxBeforeDiscount += $weeeTaxBeforeDiscount;
                    $baseWeeeTaxBeforeDiscount = $this->_calculateWeeeTax(0, $item, $rate);
                    $baseUnitTaxBeforeDiscount += $baseWeeeTaxBeforeDiscount;
                }

                $unitTaxBeforeDiscount = max(0, $this->_calculator->round($unitTaxBeforeDiscount));
                $baseUnitTaxBeforeDiscount = max(0, $this->_calculator->round($baseUnitTaxBeforeDiscount));

                if ($inclTax && $discountAmount > 0) {
                    $hiddenTax = $unitTaxBeforeDiscount - $unitTax;
                    $baseHiddenTax = $baseUnitTaxBeforeDiscount - $baseUnitTax;
                    $this->_hiddenTaxes[] = array(
                        'rate_key' => $rateKey,
                        'qty' => $qty,
                        'item' => $item,
                        'value' => $hiddenTax,
                        'base_value' => $baseHiddenTax,
                        'incl_tax' => $inclTax,
                    );
                } elseif ($discountAmount > $price) { // case with 100% discount on price incl. tax
                    $hiddenTax = $discountAmount - $price;
                    $baseHiddenTax = $baseDiscountAmount - $basePrice;
                    $this->_hiddenTaxes[] = array(
                        'rate_key' => $rateKey,
                        'qty' => $qty,
                        'item' => $item,
                        'value' => $hiddenTax,
                        'base_value' => $baseHiddenTax,
                        'incl_tax' => $inclTax,
                    );
                }
                // calculate discount compensation
                // We need the discount compensation when dont calculate the hidden taxes
                // (when product does not include taxes)
                if (!$item->getNoDiscount() && $item->getWeeeTaxApplied()) {
                    $item->setDiscountTaxCompensation($item->getDiscountTaxCompensation() +
                        $unitTaxBeforeDiscount * $qty - max(0, $unitTax) * $qty);
                }
                break;
        }

        if(Mage::helper('jpcore')->canRemoveDecimal($baseCurrency)) {
            switch($method) {
                case 'ceil':
                    $baseUnitTax = ceil($baseUnitTax);
                    break;
                case 'floor':
                    $baseUnitTax = floor($baseUnitTax);
                    break;
                case 'round':
                    $baseUnitTax = $this->_calculator->round($baseUnitTax);
                    break;
            }
        }

        if(Mage::helper('jpcore')->canRemoveDecimal($currentCurrency)) {
            switch($method) {
                case 'ceil':
                    $unitTax = ceil($unitTax);
                    break;
                case 'floor':
                    $unitTax = floor($unitTax);
                    break;
                case 'round':
                    $unitTax = $this->_calculator->round($unitTax);
                    break;
            }
        }

        $rowTax = $this->_store->roundPrice(max(0, $qty * $unitTax));
        $baseRowTax = $this->_store->roundPrice(max(0, $qty * $baseUnitTax));
        $item->setTaxAmount($item->getTaxAmount() + $rowTax);
        $item->setBaseTaxAmount($item->getBaseTaxAmount() + $baseRowTax);
        if (is_array($taxGroups)) {
            $taxGroups[$rateKey]['tax'] = max(0, $rowTax);
            $taxGroups[$rateKey]['base_tax'] = max(0, $baseRowTax);
        }

        $rowTotalInclTax = $item->getRowTotalInclTax();
        if (!isset($rowTotalInclTax) || $recalculateRowTotalInclTax) {
            if ($this->_config->priceIncludesTax($this->_store)) {
                $item->setRowTotalInclTax($price * $qty);
                $item->setBaseRowTotalInclTax($basePrice * $qty);
            } else {
                $item->setRowTotalInclTax(
                    $item->getRowTotalInclTax() + ($unitTaxBeforeDiscount - $weeeTaxBeforeDiscount) * $qty);
                $item->setBaseRowTotalInclTax(
                    $item->getBaseRowTotalInclTax() +
                    ($baseUnitTaxBeforeDiscount - $baseWeeeTaxBeforeDiscount) * $qty);
            }
        }

        return $this;
    }
}