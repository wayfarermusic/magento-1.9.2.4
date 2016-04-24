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
class Rack_Jp_Core_Model_SalesRule_Validator extends Mage_SalesRule_Model_Validator
{
    /**
     * Quote item discount calculation process
     *
     * @param   Mage_Sales_Model_Quote_Item_Abstract $item
     * @return  Mage_SalesRule_Model_Validator
     */
    public function process(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        $item->setDiscountAmount(0);
        $item->setBaseDiscountAmount(0);
        $item->setDiscountPercent(0);
        $quote      = $item->getQuote();
        $address    = $this->_getAddress($item);

        $itemPrice              = $this->_getItemPrice($item);
        $baseItemPrice          = $this->_getItemBasePrice($item);
        $itemOriginalPrice      = $this->_getItemOriginalPrice($item);
        $baseItemOriginalPrice  = $this->_getItemBaseOriginalPrice($item);

        $baseCurrency = $quote->getBaseCurrencyCode();
        $currentCurrency = $quote->getQuoteCurrencyCode();
        $round = Mage::getStoreConfig('tax/calculation/round');

        if ($itemPrice < 0) {
            return $this;
        }

        $appliedRuleIds = array();
        $this->_stopFurtherRules = false;
        foreach ($this->_getRules() as $rule) {
            //if ($this->_stopFurtherRules) {
            //    break;
            //}

            /* @var $rule Mage_SalesRule_Model_Rule */
            if (!$this->_canProcessRule($rule, $address)) {
                continue;
            }

            if (!$rule->getActions()->validate($item)) {
                continue;
            }

            $qty = $this->_getItemQty($item, $rule);
            $rulePercent = min(100, $rule->getDiscountAmount());

            $discountAmount = 0;
            $baseDiscountAmount = 0;
            //discount for original price
            $originalDiscountAmount = 0;
            $baseOriginalDiscountAmount = 0;

            switch ($rule->getSimpleAction()) {
                case Mage_SalesRule_Model_Rule::TO_PERCENT_ACTION:
                    $rulePercent = max(0, 100-$rule->getDiscountAmount());
                //no break;
                case Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION:
                    $step = $rule->getDiscountStep();
                    if ($step) {
                        $qty = floor($qty/$step)*$step;
                    }
                    $_rulePct = $rulePercent/100;
                    $discountAmount    = ($qty * $itemPrice - $item->getDiscountAmount()) * $_rulePct;
                    $baseDiscountAmount = ($qty * $baseItemPrice - $item->getBaseDiscountAmount()) * $_rulePct;
                    //get discount for original price
                    $originalDiscountAmount    = ($qty * $itemOriginalPrice - $item->getDiscountAmount()) * $_rulePct;
                    $baseOriginalDiscountAmount =
                        ($qty * $baseItemOriginalPrice - $item->getDiscountAmount()) * $_rulePct;

                    if (!$rule->getDiscountQty() || $rule->getDiscountQty()>$qty) {
                        $discountPercent = min(100, $item->getDiscountPercent()+$rulePercent);
                        $item->setDiscountPercent($discountPercent);
                    }
                    break;
                case Mage_SalesRule_Model_Rule::TO_FIXED_ACTION:
                    $quoteAmount = $quote->getStore()->convertPrice($rule->getDiscountAmount());
                    $discountAmount    = $qty * ($itemPrice-$quoteAmount);
                    $baseDiscountAmount = $qty * ($baseItemPrice-$rule->getDiscountAmount());
                    //get discount for original price
                    $originalDiscountAmount    = $qty * ($itemOriginalPrice-$quoteAmount);
                    $baseOriginalDiscountAmount = $qty * ($baseItemOriginalPrice-$rule->getDiscountAmount());
                    break;

                case Mage_SalesRule_Model_Rule::BY_FIXED_ACTION:
                    $step = $rule->getDiscountStep();
                    if ($step) {
                        $qty = floor($qty/$step)*$step;
                    }
                    $quoteAmount        = $quote->getStore()->convertPrice($rule->getDiscountAmount());
                    $discountAmount     = $qty * $quoteAmount;
                    $baseDiscountAmount = $qty * $rule->getDiscountAmount();
                    break;

                case Mage_SalesRule_Model_Rule::CART_FIXED_ACTION:
                    if (empty($this->_rulesItemTotals[$rule->getId()])) {
                        Mage::throwException(Mage::helper('salesrule')->__('Item totals are not set for rule.'));
                    }

                    /**
                     * prevent applying whole cart discount for every shipping order, but only for first order
                     */
                    if ($quote->getIsMultiShipping()) {
                        $usedForAddressId = $this->getCartFixedRuleUsedForAddress($rule->getId());
                        if ($usedForAddressId && $usedForAddressId != $address->getId()) {
                            break;
                        } else {
                            $this->setCartFixedRuleUsedForAddress($rule->getId(), $address->getId());
                        }
                    }
                    $cartRules = $address->getCartFixedRules();
                    if (!isset($cartRules[$rule->getId()])) {
                        $cartRules[$rule->getId()] = $rule->getDiscountAmount();
                    }

                    if ($cartRules[$rule->getId()] > 0) {
                        if ($this->_rulesItemTotals[$rule->getId()]['items_count'] <= 1) {
                            $quoteAmount = $quote->getStore()->convertPrice($cartRules[$rule->getId()]);
                            $baseDiscountAmount = min($baseItemPrice * $qty, $cartRules[$rule->getId()]);
                        } else {
                            $discountRate = $baseItemPrice * $qty /
                                $this->_rulesItemTotals[$rule->getId()]['base_items_price'];
                            $maximumItemDiscount = $rule->getDiscountAmount() * $discountRate;
                            $quoteAmount = $quote->getStore()->convertPrice($maximumItemDiscount);

                            $baseDiscountAmount = min($baseItemPrice * $qty, $maximumItemDiscount);
                            $this->_rulesItemTotals[$rule->getId()]['items_count']--;
                        }

                        $discountAmount = min($itemPrice * $qty, $quoteAmount);
                        $discountAmount = $quote->getStore()->roundPrice($discountAmount);
                        $baseDiscountAmount = $quote->getStore()->roundPrice($baseDiscountAmount);

                        //get discount for original price
                        $originalDiscountAmount = min($itemOriginalPrice * $qty, $quoteAmount);
                        $baseOriginalDiscountAmount = $quote->getStore()->roundPrice($baseItemOriginalPrice);

                        $cartRules[$rule->getId()] -= $baseDiscountAmount;
                    }
                    $address->setCartFixedRules($cartRules);

                    break;

                case Mage_SalesRule_Model_Rule::BUY_X_GET_Y_ACTION:
                    $x = $rule->getDiscountStep();
                    $y = $rule->getDiscountAmount();
                    if (!$x || $y > $x) {
                        break;
                    }
                    $buyAndDiscountQty = $x + $y;

                    $fullRuleQtyPeriod = floor($qty / $buyAndDiscountQty);
                    $freeQty  = $qty - $fullRuleQtyPeriod * $buyAndDiscountQty;

                    $discountQty = $fullRuleQtyPeriod * $y;
                    if ($freeQty > $x) {
                        $discountQty += $freeQty - $x;
                    }

                    $discountAmount    = $discountQty * $itemPrice;
                    $baseDiscountAmount = $discountQty * $baseItemPrice;
                    //get discount for original price
                    $originalDiscountAmount    = $discountQty * $itemOriginalPrice;
                    $baseOriginalDiscountAmount = $discountQty * $baseItemOriginalPrice;
                    break;
            }

            $result = new Varien_Object(array(
                'discount_amount'      => $discountAmount,
                'base_discount_amount' => $baseDiscountAmount,
            ));
            Mage::dispatchEvent('salesrule_validator_process', array(
                'rule'    => $rule,
                'item'    => $item,
                'address' => $address,
                'quote'   => $quote,
                'qty'     => $qty,
                'result'  => $result,
            ));

            $discountAmount = $result->getDiscountAmount();
            $baseDiscountAmount = $result->getBaseDiscountAmount();

            $percentKey = $item->getDiscountPercent();
            /**
             * Process "delta" rounding
             */
            if ($percentKey) {
                $delta      = isset($this->_roundingDeltas[$percentKey]) ? $this->_roundingDeltas[$percentKey] : 0;
                $baseDelta  = isset($this->_baseRoundingDeltas[$percentKey])
                    ? $this->_baseRoundingDeltas[$percentKey]
                    : 0;
                $discountAmount += $delta;
                $baseDiscountAmount += $baseDelta;

                $this->_roundingDeltas[$percentKey]     = $discountAmount -
                    $quote->getStore()->roundPrice($discountAmount);
                $this->_baseRoundingDeltas[$percentKey] = $baseDiscountAmount -
                    $quote->getStore()->roundPrice($baseDiscountAmount);
                $discountAmount = $quote->getStore()->roundPrice($discountAmount);
                $baseDiscountAmount = $quote->getStore()->roundPrice($baseDiscountAmount);
            } else {
                $discountAmount     = $quote->getStore()->roundPrice($discountAmount);
                $baseDiscountAmount = $quote->getStore()->roundPrice($baseDiscountAmount);
            }

            /**
             * We can't use row total here because row total not include tax
             * Discount can be applied on price included tax
             */

            $itemDiscountAmount = $item->getDiscountAmount();
            $itemBaseDiscountAmount = $item->getBaseDiscountAmount();

            if(Mage::helper('jpcore')->canRemoveDecimal($currentCurrency)) {
                switch($round) {
                    case 'ceil':
                        $discountAmount = ceil($discountAmount);
                        $itemDiscountAmount = ceil($itemDiscountAmount);
                        break;
                    case 'floor':
                        $discountAmount = floor($discountAmount);
                        $itemDiscountAmount = floor($itemDiscountAmount);
                        break;
                    case 'round':
                        $discountAmount = $quote->getStore()->roundPrice($discountAmount);
                        $itemDiscountAmount = $quote->getStore()->roundPrice($itemDiscountAmount);
                        break;
                }
            }

            if(Mage::helper('jpcore')->canRemoveDecimal($baseCurrency)) {
                switch($round) {
                    case 'ceil':
                        $baseDiscountAmount = ceil($baseDiscountAmount);
                        $itemBaseDiscountAmount = ceil($itemBaseDiscountAmount);
                        break;
                    case 'floor':
                        $baseDiscountAmount = floor($baseDiscountAmount);
                        $itemBaseDiscountAmount = floor($itemBaseDiscountAmount);
                        break;
                    case 'round':
                        $baseDiscountAmount = $quote->getStore()->roundPrice($baseDiscountAmount);
                        $itemBaseDiscountAmount = $quote->getStore()->roundPrice($itemBaseDiscountAmount);
                        break;
                }
            }

            $discountAmount     = min($itemDiscountAmount + $discountAmount, $itemPrice * $qty);
            $baseDiscountAmount = min($itemBaseDiscountAmount + $baseDiscountAmount, $baseItemPrice * $qty);

            $item->setDiscountAmount($discountAmount);
            $item->setBaseDiscountAmount($baseDiscountAmount);

            $item->setOriginalDiscountAmount($originalDiscountAmount);
            $item->setBaseOriginalDiscountAmount($baseOriginalDiscountAmount);

            $appliedRuleIds[$rule->getRuleId()] = $rule->getRuleId();

            $this->_maintainAddressCouponCode($address, $rule);
            $this->_addDiscountDescription($address, $rule);

            if ($rule->getStopRulesProcessing()) {
                $this->_stopFurtherRules = true;
                break;
            }
        }

        $item->setAppliedRuleIds(join(',',$appliedRuleIds));
        $address->setAppliedRuleIds($this->mergeIds($address->getAppliedRuleIds(), $appliedRuleIds));
        $quote->setAppliedRuleIds($this->mergeIds($quote->getAppliedRuleIds(), $appliedRuleIds));

        return $this;
    }

    /**
     * Apply discount amount to FPT
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @param array $items
     * @return Mage_SalesRule_Model_Validator
     */
    public function processWeeeAmount(Mage_Sales_Model_Quote_Address $address, $items)
    {
        $quote = $address->getQuote();
        $store = $quote->getStore();

        if (!$this->_getHelper('weee')->isEnabled() || !$this->_getHelper('weee')->isDiscounted()) {
            return $this;
        }

        /**
         *   for calculating weee tax discount
         */
        $config = $this->_getSingleton('tax/config');
        $calculator = $this->_getSingleton('tax/calculation');
        $request = $calculator->getRateRequest(
            $address,
            $quote->getBillingAddress(),
            $quote->getCustomerTaxClassId(),
            $store
        );

        $applyTaxAfterDiscount = $config->applyTaxAfterDiscount();
        $discountTax = $config->discountTax();
        $includeInSubtotal = $this->_getHelper('weee')->includeInSubtotal();

        foreach ($this->_getRules() as $rule) {
            /* @var $rule Mage_SalesRule_Model_Rule */
            $rulePercent = min(100, $rule->getDiscountAmount());
            switch ($rule->getSimpleAction()) {
                case Mage_SalesRule_Model_Rule::TO_PERCENT_ACTION:
                    $rulePercent = max(0, 100 - $rule->getDiscountAmount());
                case Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION:
                    foreach ($items as $item) {

                        $weeeTaxAppliedAmounts = $this->_getHelper('weee')->getApplied($item);

                        //Total weee discount for the item
                        $totalWeeeDiscount = 0;
                        $totalBaseWeeeDiscount = 0;

                        foreach ($weeeTaxAppliedAmounts as $weeeTaxAppliedAmount) {

                            /* we get the discount by row since we dont need to display the individual amounts */
                            $weeeTaxAppliedRowAmount = $weeeTaxAppliedAmount['row_amount'];
                            $baseWeeeTaxAppliedRowAmount = $weeeTaxAppliedAmount['base_row_amount'];
                            $request->setProductClassId($item->getProduct()->getTaxClassId());
                            $rate = $calculator->getRate($request);

                            /*
                             * calculate weee discount
                             */
                            $weeeDiscount = 0;
                            $baseWeeeDiscount = 0;

                            if ($this->_getHelper('weee')->isTaxable()) {
                                if ($applyTaxAfterDiscount) {
                                    if ($discountTax) {
                                        $weeeTax = $weeeTaxAppliedRowAmount * $rate / 100;
                                        $baseWeeeTax = $baseWeeeTaxAppliedRowAmount * $rate / 100;
                                        $weeeDiscount = ($weeeTaxAppliedRowAmount + $weeeTax) * $rulePercent / 100;
                                        $baseWeeeDiscount = ($baseWeeeTaxAppliedRowAmount + $baseWeeeTax)
                                            * $rulePercent / 100;
                                    } else {
                                        $weeeDiscount = $weeeTaxAppliedRowAmount * $rulePercent / 100;
                                        $baseWeeeDiscount = $baseWeeeTaxAppliedRowAmount * $rulePercent / 100;
                                    }
                                } else {
                                    if ($discountTax) {
                                        $weeeTax = $weeeTaxAppliedRowAmount * $rate / 100;
                                        $baseWeeeTax = $baseWeeeTaxAppliedRowAmount * $rate / 100;
                                        $weeeDiscount = ($weeeTaxAppliedRowAmount + $weeeTax) * $rulePercent / 100;
                                        $baseWeeeDiscount = ($baseWeeeTaxAppliedRowAmount + $baseWeeeTax)
                                            * $rulePercent / 100;
                                    } else {
                                        $weeeDiscount = $weeeTaxAppliedRowAmount * $rulePercent / 100;
                                        $baseWeeeDiscount = $baseWeeeTaxAppliedRowAmount * $rulePercent / 100;
                                    }
                                }
                            } else {
                                // weee is not taxable
                                $weeeDiscount = $weeeTaxAppliedRowAmount * $rulePercent / 100;
                                $baseWeeeDiscount = $baseWeeeTaxAppliedRowAmount * $rulePercent / 100;
                            }

                            if (!$includeInSubtotal) {
                                $this->_getHelper('weee')->setWeeeTaxesAppliedProperty(
                                    $item, $weeeTaxAppliedAmount['title'], 'weee_discount', $weeeDiscount);
                                $this->_getHelper('weee')->setWeeeTaxesAppliedProperty(
                                    $item, $weeeTaxAppliedAmount['title'], 'base_weee_discount', $baseWeeeDiscount);
                            }

                            //Record the total weee discount
                            $totalBaseWeeeDiscount += $baseWeeeDiscount;
                            $totalWeeeDiscount += $weeeDiscount;
                        }

                        if (!$totalBaseWeeeDiscount && !$totalWeeeDiscount) {
                            //skip further processing if there is no weee discount associated with the item
                            continue;
                        }

                        $discountPercentage = $item->getDiscountPercent();

                        $totalWeeeDiscount = $this->_roundWithDeltas($discountPercentage,
                            $totalWeeeDiscount, $quote->getStore());
                        $totalBaseWeeeDiscount = $this->_roundWithDeltasForBase($discountPercentage,
                            $totalBaseWeeeDiscount, $quote->getStore());

                        $item->setWeeeDiscount($totalWeeeDiscount);
                        $item->setBaseWeeeDiscount($totalBaseWeeeDiscount);

                        //Set the total discount replicated on all weee attributes.
                        //we need to do this as the mage_sales_order_item does not store the weee discount
                        //We need to store this as we want to keep the rounded amounts
                        if (!$includeInSubtotal) {
                            $this->_getHelper('weee')->setWeeeTaxesAppliedProperty(
                                $item, null, 'total_base_weee_discount', $totalBaseWeeeDiscount);
                            $this->_getHelper('weee')->setWeeeTaxesAppliedProperty(
                                $item, null, 'total_weee_discount', $totalWeeeDiscount);
                        }

                        if ($includeInSubtotal) {
                            $item->setDiscountAmount($item->getDiscountAmount() + $totalWeeeDiscount);
                            $item->setBaseDiscountAmount($item->getBaseDiscountAmount() + $totalBaseWeeeDiscount);
                            $address->addTotalAmount('discount', -$totalWeeeDiscount);
                            $address->addBaseTotalAmount('discount', -$totalBaseWeeeDiscount);
                        } else {
                            if ($applyTaxAfterDiscount) {
                                $address->setExtraTaxAmount($address->getExtraTaxAmount() - $totalWeeeDiscount);
                                $address->setBaseExtraTaxAmount(
                                    $address->getBaseExtraTaxAmount() - $totalBaseWeeeDiscount);
                                $address->setWeeeDiscount($address->getWeeeDiscount() + $totalWeeeDiscount);
                                $address->setBaseWeeeDiscount($address->getBaseWeeeDiscount() + $totalBaseWeeeDiscount);
                            } else {
                                //tax has already been calculated, we need to remove weeeDiscount from total tax
                                $address->setExtraTaxAmount($address->getExtraTaxAmount() - $totalWeeeDiscount);
                                $address->setBaseExtraTaxAmount(
                                    $address->getBaseExtraTaxAmount() - $totalBaseWeeeDiscount);
                                $address->addTotalAmount('tax', -$totalWeeeDiscount);
                                $address->addBaseTotalAmount('tax', -$totalBaseWeeeDiscount);
                                $address->setWeeeDiscount($address->getWeeeDiscount() + $totalWeeeDiscount);
                                $address->setBaseWeeeDiscount($address->getBaseWeeeDiscount() + $totalBaseWeeeDiscount);
                            }
                        }

                        break;
                    }
            }
        }
        return $this;
    }

    /**
     * Round the amount with deltas collected
     *
     * @param string $key
     * @param float $amount
     * @param Mage_Core_Model_Store $store
     * @return float
     */
    protected function _roundWithDeltas($key, $amount, $store)
    {
        $delta = isset($this->_roundingDeltas[$key]) ?
            $this->_roundingDeltas[$key] : 0;
        $this->_roundingDeltas[$key] = $store->roundPrice($amount + $delta)
            - $amount;
        return $store->roundPrice($amount + $delta);
    }

    /**
     * Round the amount with deltas collected
     *
     * @param string $key
     * @param float $amount
     * @param Mage_Core_Model_Store $store
     * @return float
     */
    protected function _roundWithDeltasForBase($key, $amount, $store)
    {
        $delta = isset($this->_baseRoundingDeltas[$key]) ?
            $this->_roundingDeltas[$key] : 0;
        $this->_baseRoundingDeltas[$key] = $store->roundPrice($amount + $delta)
            - $amount;
        return $store->roundPrice($amount + $delta);
    }

    /**
     * Apply discounts to shipping amount
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_SalesRule_Model_Validator
     */
    public function processShippingAmount(Mage_Sales_Model_Quote_Address $address)
    {
        $baseCurrency = $address->getQuote()->getBaseCurrencyCode();
        $currentCurrency = $address->getQuote()->getQuoteCurrencyCode();
        $round = Mage::getStoreConfig('tax/calculation/round');
        $shippingAmount = $address->getShippingAmountForDiscount();
        if ($shippingAmount !== null) {
            $baseShippingAmount = $address->getBaseShippingAmountForDiscount();
        } else {
            $shippingAmount     = $address->getShippingAmount();
            $baseShippingAmount = $address->getBaseShippingAmount();
        }
        $quote              = $address->getQuote();
        $appliedRuleIds = array();
        foreach ($this->_getRules() as $rule) {
            /* @var $rule Mage_SalesRule_Model_Rule */
            if (!$rule->getApplyToShipping() || !$this->_canProcessRule($rule, $address)) {
                continue;
            }

            $discountAmount = 0;
            $baseDiscountAmount = 0;
            $rulePercent = min(100, $rule->getDiscountAmount());
            switch ($rule->getSimpleAction()) {
                case Mage_SalesRule_Model_Rule::TO_PERCENT_ACTION:
                    $rulePercent = max(0, 100-$rule->getDiscountAmount());
                case Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION:
                    $discountAmount    = ($shippingAmount - $address->getShippingDiscountAmount()) * $rulePercent/100;
                    $baseDiscountAmount = ($baseShippingAmount -
                            $address->getBaseShippingDiscountAmount()) * $rulePercent/100;
                    $discountPercent = min(100, $address->getShippingDiscountPercent()+$rulePercent);
                    $address->setShippingDiscountPercent($discountPercent);
                    break;
                case Mage_SalesRule_Model_Rule::TO_FIXED_ACTION:
                    $quoteAmount = $quote->getStore()->convertPrice($rule->getDiscountAmount());
                    $discountAmount    = $shippingAmount-$quoteAmount;
                    $baseDiscountAmount = $baseShippingAmount-$rule->getDiscountAmount();
                    break;
                case Mage_SalesRule_Model_Rule::BY_FIXED_ACTION:
                    $quoteAmount        = $quote->getStore()->convertPrice($rule->getDiscountAmount());
                    $discountAmount     = $quoteAmount;
                    $baseDiscountAmount = $rule->getDiscountAmount();
                    break;
                case Mage_SalesRule_Model_Rule::CART_FIXED_ACTION:
                    $cartRules = $address->getCartFixedRules();
                    if (!isset($cartRules[$rule->getId()])) {
                        $cartRules[$rule->getId()] = $rule->getDiscountAmount();
                    }
                    if ($cartRules[$rule->getId()] > 0) {
                        $quoteAmount        = $quote->getStore()->convertPrice($cartRules[$rule->getId()]);
                        $discountAmount     = min(
                            $shippingAmount-$address->getShippingDiscountAmount(),
                            $quoteAmount
                        );
                        $baseDiscountAmount = min(
                            $baseShippingAmount-$address->getBaseShippingDiscountAmount(),
                            $cartRules[$rule->getId()]
                        );
                        $cartRules[$rule->getId()] -= $baseDiscountAmount;
                    }

                    $address->setCartFixedRules($cartRules);
                    break;
            }

            $discountAmount     = min($address->getShippingDiscountAmount()+$discountAmount, $shippingAmount);
            $baseDiscountAmount = min(
                $address->getBaseShippingDiscountAmount()+$baseDiscountAmount,
                $baseShippingAmount
            );

            if(Mage::helper('jpcore')->canRemoveDecimal($currentCurrency)) {
                switch($round) {
                    case 'ceil':
                        $discountAmount = ceil($discountAmount);
                        break;
                    case 'floor':
                        $discountAmount = floor($discountAmount);
                        break;
                    case 'round':
                        $discountAmount = $quote->getStore()->roundPrice($discountAmount);
                        break;
                }
            }

            if(Mage::helper('jpcore')->canRemoveDecimal($baseCurrency)) {
                switch($round) {
                    case 'ceil':
                        $baseDiscountAmount = ceil($baseDiscountAmount);
                        break;
                    case 'floor':
                        $baseDiscountAmount = floor($baseDiscountAmount);
                        break;
                    case 'round':
                        $baseDiscountAmount = $quote->getStore()->roundPrice($baseDiscountAmount);
                        break;
                }
            }

            $address->setShippingDiscountAmount($discountAmount);
            $address->setBaseShippingDiscountAmount($baseDiscountAmount);
            $appliedRuleIds[$rule->getRuleId()] = $rule->getRuleId();

            $this->_maintainAddressCouponCode($address, $rule);
            $this->_addDiscountDescription($address, $rule);
            if ($rule->getStopRulesProcessing()) {
                break;
            }
        }

        $address->setAppliedRuleIds($this->mergeIds($address->getAppliedRuleIds(), $appliedRuleIds));
        $quote->setAppliedRuleIds($this->mergeIds($quote->getAppliedRuleIds(), $appliedRuleIds));

        return $this;
    }
}