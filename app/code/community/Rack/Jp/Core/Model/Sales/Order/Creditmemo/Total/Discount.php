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
class Rack_Jp_Core_Model_Sales_Order_Creditmemo_Total_Discount extends Mage_Sales_Model_Order_Creditmemo_Total_Discount
{
    /**
     * collect total for credit memo discount.
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $creditmemo->setDiscountAmount(0);
        $creditmemo->setBaseDiscountAmount(0);

        $order = $creditmemo->getOrder();

        $totalDiscountAmount = 0;
        $baseTotalDiscountAmount = 0;

        /**
         * Calculate how much shipping discount should be applied
         * basing on how much shipping should be refunded.
         */
        $baseShippingAmount = $creditmemo->getBaseShippingAmount();
        if ($baseShippingAmount) {
            $baseShippingDiscount = $baseShippingAmount * $order->getBaseShippingDiscountAmount() / $order->getBaseShippingAmount();
            $shippingDiscount = $order->getShippingAmount() * $baseShippingDiscount / $order->getBaseShippingAmount();
            $totalDiscountAmount = $totalDiscountAmount + $shippingDiscount;
            $baseTotalDiscountAmount = $baseTotalDiscountAmount + $baseShippingDiscount;
        }

        $current = $creditmemo->getOrderCurrencyCode();
        $base = $creditmemo->getBaseCurrencyCode();
        $round = Mage::getStoreConfig('tax/calculation/round');

        $baseDelta = 0;
        $delta = 0;

        /** @var $item Mage_Sales_Model_Order_Invoice_Item */
        foreach ($creditmemo->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();

            if ($orderItem->isDummy()) {
                continue;
            }

            $orderItemDiscount      = (float) $orderItem->getDiscountInvoiced();
            $baseOrderItemDiscount  = (float) $orderItem->getBaseDiscountInvoiced();
            $orderItemQty           = $orderItem->getQtyInvoiced();

            if ($orderItemDiscount && $orderItemQty) {
                $discount = $orderItemDiscount - $orderItem->getDiscountRefunded();
                $baseDiscount = $baseOrderItemDiscount - $orderItem->getBaseDiscountRefunded();
                if (!$item->isLast()) {
                    $availableQty = $orderItemQty - $orderItem->getQtyRefunded();
                    $discount = $creditmemo->roundPrice(
                        $discount / $availableQty * $item->getQty(), 'regular', true
                    );
                    $baseDiscount = $creditmemo->roundPrice(
                        $baseDiscount / $availableQty * $item->getQty(), 'base', true
                    );
                }

                if(Mage::helper('jpcore')->canRemoveDecimal($current)) {
                    switch($round) {
                        case 'ceil':
                            $delta += ceil($discount) - $discount;

                            $discount = ceil($discount);
                            break;
                        case 'floor':
                            $delta += $discount - floor($discount);

                            $discount = floor($discount);
                            break;
                    }
                }

                if(Mage::helper('jpcore')->canRemoveDecimal($base)) {
                    switch($round) {
                        case 'ceil':
                            $baseDelta += ceil($baseDiscount) - $baseDiscount;
                            $baseDiscount = ceil($baseDiscount);
                            break;
                        case 'floor':
                            $baseDelta += $baseDiscount - floor($baseDiscount);
                            $baseDiscount = floor($baseDiscount);
                            break;
                    }
                }

                if($item->isLast()) {
                    $discount -= floor($delta);
                    $baseDiscount -= floor($baseDelta);
                }

                $item->setDiscountAmount($discount);
                $item->setBaseDiscountAmount($baseDiscount);

                $totalDiscountAmount += $discount;
                $baseTotalDiscountAmount+= $baseDiscount;
            }
        }

        if(Mage::helper('jpcore')->canRemoveDecimal($current)) {
            switch($round) {
                case 'ceil':
                    $totalDiscountAmount = ceil($totalDiscountAmount);
                    break;
                case 'floor':
                    $totalDiscountAmount = floor($totalDiscountAmount);
                    break;
            }
        }

        if(Mage::helper('jpcore')->canRemoveDecimal($base)) {
            switch($round) {
                case 'ceil':
                    $baseTotalDiscountAmount = ceil($baseTotalDiscountAmount);
                    break;
                case 'floor':
                    $baseTotalDiscountAmount = floor($baseTotalDiscountAmount);
                    break;
            }
        }

        $_edition = Mage::getEdition();
        $_version = Mage::getVersionInfo();

        if($_edition == Mage::EDITION_COMMUNITY) {
            switch($_version['minor']) {
                case '8':
                case '9':
                    $creditmemo->setDiscountAmount(-$totalDiscountAmount);
                    $creditmemo->setBaseDiscountAmount(-$baseTotalDiscountAmount);
                    break;
                case '7':
                default:
                    $creditmemo->setDiscountAmount($totalDiscountAmount);
                    $creditmemo->setBaseDiscountAmount($baseTotalDiscountAmount);
                    break;
            }
        } else {
            switch($_version['minor']) {
                case '14':
                case '13':
                    $creditmemo->setDiscountAmount(-$totalDiscountAmount);
                    $creditmemo->setBaseDiscountAmount(-$baseTotalDiscountAmount);
                    break;
                case '12':
                default:
                    $creditmemo->setDiscountAmount($totalDiscountAmount);
                    $creditmemo->setBaseDiscountAmount($baseTotalDiscountAmount);
                    break;
            }
        }

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() - $totalDiscountAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() - $baseTotalDiscountAmount);
        return $this;
    }
}