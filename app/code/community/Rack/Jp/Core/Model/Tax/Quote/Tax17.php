<?php
class Rack_Jp_Core_Model_Tax_Quote_Tax17 extends Mage_Tax_Model_Sales_Total_Quote_Tax
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
            $totalTax = $this->_calculator->calcTaxAmount(array_sum($data['totals']), $rate, $inclTax);
            $baseTotalTax = $this->_calculator->calcTaxAmount(array_sum($data['base_totals']), $rate, $inclTax);

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
            $this->_saveAppliedTaxes($address, $data['applied_rates'], $totalTax, $baseTotalTax, $rate);
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
    protected function _aggregateTaxPerRate($item, $rate, &$taxGroups)
    {
        $inclTax        = $item->getIsPriceInclTax();
        $rateKey        = (string) $rate;
        $taxSubtotal    = $subtotal     = $item->getTaxableAmount() + $item->getExtraRowTaxableAmount();
        $baseTaxSubtotal= $baseSubtotal = $item->getBaseTaxableAmount() + $item->getBaseExtraRowTaxableAmount();
        $item->setTaxPercent($rate);

        if (!isset($taxGroups[$rateKey]['totals'])) {
            $taxGroups[$rateKey]['totals'] = array();
            $taxGroups[$rateKey]['base_totals'] = array();
        }

        $hiddenTax      = null;
        $baseHiddenTax  = null;
        switch ($this->_helper->getCalculationSequence($this->_store)) {
            case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_EXCL:
            case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_INCL:
                $rowTax             = $this->_calculator->calcTaxAmount($subtotal, $rate, $inclTax, false);
                $baseRowTax         = $this->_calculator->calcTaxAmount($baseSubtotal, $rate, $inclTax, false);
                break;
            case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_EXCL:
            case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_INCL:
                if ($this->_helper->applyTaxOnOriginalPrice($this->_store)) {
                    $discount           = $item->getOriginalDiscountAmount();
                    $baseDiscount       = $item->getBaseOriginalDiscountAmount();
                } else {
                    $discount           = $item->getDiscountAmount();
                    $baseDiscount       = $item->getBaseDiscountAmount();
                }

                $taxSubtotal        = max($subtotal - $discount, 0);
                $baseTaxSubtotal    = max($baseSubtotal - $baseDiscount, 0);
                $rowTax             = $this->_calculator->calcTaxAmount($taxSubtotal, $rate, $inclTax, false);
                $baseRowTax         = $this->_calculator->calcTaxAmount($baseTaxSubtotal, $rate, $inclTax, false);
                if (!$item->getNoDiscount() && $item->getWeeeTaxApplied()) {
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
                }

                if ($inclTax && $discount > 0) {
                    $hiddenTax      = $this->_calculator->calcTaxAmount($discount, $rate, $inclTax, false);
                    $baseHiddenTax  = $this->_calculator->calcTaxAmount($baseDiscount, $rate, $inclTax, false);
                    $this->_hiddenTaxes[] = array(
                        'rate_key'   => $rateKey,
                        'qty'        => 1,
                        'item'       => $item,
                        'value'      => $hiddenTax,
                        'base_value' => $baseHiddenTax,
                        'incl_tax'   => $inclTax,
                    );
                }
                break;
        }

        $method = Mage::getStoreConfig('tax/calculation/round');
        $currentCurrency = $item->getQuote()->getQuoteCurrencyCode();
        $baseCurrency = $item->getQuote()->getBaseCurrencyCode();

        if(Mage::helper('jpcore')->canRemoveDecimal($currentCurrency)) {
            $rowTax = $method($rowTax);
        }

        if(Mage::helper('jpcore')->canRemoveDecimal($baseCurrency)) {
            $baseRowTax = $method($baseRowTax);
        }

        $rowTax     = $this->_deltaRound($rowTax, $rateKey, $inclTax);
        $baseRowTax = $this->_deltaRound($baseRowTax, $rateKey, $inclTax, 'base');
        $item->setTaxAmount(max(0, $rowTax));
        $item->setBaseTaxAmount(max(0, $baseRowTax));

        if (isset($rowTaxBeforeDiscount) && isset($baseRowTaxBeforeDiscount)) {
            $taxBeforeDiscount = max(
                0,
                $this->_deltaRound($rowTaxBeforeDiscount, $rateKey, $inclTax)
            );
            $baseTaxBeforeDiscount = max(
                0,
                $this->_deltaRound($baseRowTaxBeforeDiscount, $rateKey, $inclTax, 'base')
            );

            $item->setDiscountTaxCompensation($taxBeforeDiscount - max(0, $rowTax));
            $item->setBaseDiscountTaxCompensation($baseTaxBeforeDiscount - max(0, $baseRowTax));
        }

        $taxGroups[$rateKey]['totals'][]        = max(0, $taxSubtotal);
        $taxGroups[$rateKey]['base_totals'][]   = max(0, $baseTaxSubtotal);
        return $this;
    }

    /**
     * Calculate unit tax anount based on unit price
     *
     * @param   Mage_Sales_Model_Quote_Item_Abstract $item
     * @param   float $rate
     * @return  Mage_Tax_Model_Sales_Total_Quote
     */
    protected function _calcUnitTaxAmount(Mage_Sales_Model_Quote_Item_Abstract $item, $rate)
    {
        $qty        = $item->getTotalQty();
        $inclTax    = $item->getIsPriceInclTax();
        $price      = $item->getTaxableAmount() + $item->getExtraTaxableAmount();
        $basePrice  = $item->getBaseTaxableAmount() + $item->getBaseExtraTaxableAmount();
        $rateKey    = (string)$rate;
        $item->setTaxPercent($rate);

        $hiddenTax      = null;
        $baseHiddenTax  = null;
        switch ($this->_config->getCalculationSequence($this->_store)) {
            case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_EXCL:
            case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_INCL:
                $unitTax        = $this->_calculator->calcTaxAmount($price, $rate, $inclTax);
                $baseUnitTax    = $this->_calculator->calcTaxAmount($basePrice, $rate, $inclTax);
                break;
            case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_EXCL:
            case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_INCL:
                $discountAmount     = $item->getDiscountAmount() / $qty;
                $baseDiscountAmount = $item->getBaseDiscountAmount() / $qty;

                $unitTax = $this->_calculator->calcTaxAmount($price, $rate, $inclTax);
                $discountRate = ($unitTax/$price) * 100;
                $unitTaxDiscount = $this->_calculator->calcTaxAmount($discountAmount, $discountRate, $inclTax, false);
                $unitTax = max($unitTax - $unitTaxDiscount, 0);
                $baseUnitTax = $this->_calculator->calcTaxAmount($basePrice, $rate, $inclTax);
                $baseDiscountRate = ($baseUnitTax/$basePrice) * 100;
                $baseUnitTaxDiscount = $this->_calculator
                    ->calcTaxAmount($baseDiscountAmount, $baseDiscountRate, $inclTax, false);
                $baseUnitTax = max($baseUnitTax - $baseUnitTaxDiscount, 0);

                if ($inclTax && $discountAmount > 0) {
                    $hiddenTax      = $this->_calculator->calcTaxAmount($discountAmount, $rate, $inclTax, false);
                    $baseHiddenTax  = $this->_calculator->calcTaxAmount($baseDiscountAmount, $rate, $inclTax, false);
                    $this->_hiddenTaxes[] = array(
                        'rate_key'   => $rateKey,
                        'qty'        => $qty,
                        'item'       => $item,
                        'value'      => $hiddenTax,
                        'base_value' => $baseHiddenTax,
                        'incl_tax'   => $inclTax,
                    );
                } elseif ($discountAmount > $price) { // case with 100% discount on price incl. tax
                    $hiddenTax      = $discountAmount - $price;
                    $baseHiddenTax  = $baseDiscountAmount - $basePrice;
                    $this->_hiddenTaxes[] = array(
                        'rate_key'   => $rateKey,
                        'qty'        => $qty,
                        'item'       => $item,
                        'value'      => $hiddenTax,
                        'base_value' => $baseHiddenTax,
                        'incl_tax'   => $inclTax,
                    );
                }
                break;
        }

        $baseCurrency = $item->getQuote()->getBaseCurrencyCode();
        $currentCurrency = $item->getQuote()->getQuoteCurrencyCode();
        $method = Mage::getStoreConfig('tax/calculation/round');

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

        $item->setTaxAmount($this->_store->roundPrice(max(0, $qty*$unitTax)));
        $item->setBaseTaxAmount($this->_store->roundPrice(max(0, $qty*$baseUnitTax)));

        return $this;
    }

    /**
     * Tax caclulation for shipping price
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @param   Varien_Object $taxRateRequest
     * @return  Mage_Tax_Model_Sales_Total_Quote
     */
    protected function _calculateShippingTax(Mage_Sales_Model_Quote_Address $address, $taxRateRequest)
    {
        $taxRateRequest->setProductClassId($this->_config->getShippingTaxClass($this->_store));
        $rate           = $this->_calculator->getRate($taxRateRequest);
        $inclTax        = $address->getIsShippingInclTax();
        $shipping       = $address->getShippingTaxable();
        $baseShipping   = $address->getBaseShippingTaxable();
        $rateKey        = (string)$rate;

        $hiddenTax      = null;
        $baseHiddenTax  = null;
        switch ($this->_helper->getCalculationSequence($this->_store)) {
            case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_EXCL:
            case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_INCL:
                $tax        = $this->_calculator->calcTaxAmount($shipping, $rate, $inclTax, false);
                $baseTax    = $this->_calculator->calcTaxAmount($baseShipping, $rate, $inclTax, false);
                break;
            case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_EXCL:
            case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_INCL:
                $discountAmount     = $address->getShippingDiscountAmount();
                $baseDiscountAmount = $address->getBaseShippingDiscountAmount();
                $tax = $this->_calculator->calcTaxAmount(
                    $shipping - $discountAmount,
                    $rate,
                    $inclTax,
                    false
                );
                $baseTax = $this->_calculator->calcTaxAmount(
                    $baseShipping - $baseDiscountAmount,
                    $rate,
                    $inclTax,
                    false
                );
                break;
        }

        if ($this->_config->getAlgorithm($this->_store) == Mage_Tax_Model_Calculation::CALC_TOTAL_BASE) {
            $tax        = $this->_deltaRound($tax, $rate, $inclTax);
            $baseTax    = $this->_deltaRound($baseTax, $rate, $inclTax, 'base');
        } else {
            $tax        = $this->_calculator->round($tax);
            $baseTax    = $this->_calculator->round($baseTax);
        }

        if ($inclTax && !empty($discountAmount)) {
            $hiddenTax      = $this->_calculator->calcTaxAmount($discountAmount, $rate, $inclTax, false);
            $baseHiddenTax  = $this->_calculator->calcTaxAmount($baseDiscountAmount, $rate, $inclTax, false);
            $this->_hiddenTaxes[] = array(
                'rate_key'   => $rateKey,
                'value'      => $hiddenTax,
                'base_value' => $baseHiddenTax,
                'incl_tax'   => $inclTax,
            );
        }

        $baseCurrency = $address->getQuote()->getBaseCurrencyCode();
        $currentCurrency = $address->getQuote()->getQuoteCurrencyCode();
        $method = Mage::getStoreConfig('tax/calculation/round');

        if(Mage::helper('jpcore')->canRemoveDecimal($currentCurrency)) {
            switch($method) {
                case 'ceil':
                    $tax = ceil($tax);
                    break;
                case 'floor':
                    $tax = floor($tax);
                    break;
                case 'round':
                    $tax = $this->_calculator->round($tax);
                    break;
            }
        }

        if(Mage::helper('jpcore')->canRemoveDecimal($baseCurrency)) {
            switch($method) {
                case 'ceil':
                    $baseTax = ceil($baseTax);
                    break;
                case 'floor':
                    $baseTax = floor($baseTax);
                    break;
                case 'round':
                    $baseTax = $this->_calculator->round($baseTax);
                    break;
            }
        }

        $this->_addAmount(max(0, $tax));
        $this->_addBaseAmount(max(0, $baseTax));
        $address->setShippingTaxAmount(max(0, $tax));
        $address->setBaseShippingTaxAmount(max(0, $baseTax));
        $applied = $this->_calculator->getAppliedRates($taxRateRequest);
        $this->_saveAppliedTaxes($address, $applied, $tax, $baseTax, $rate);

        return $this;
    }
}