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
class Rack_Jp_Core_Model_Sales_Order_Invoice_Total_Discount extends Mage_Sales_Model_Order_Invoice_Total_Discount
{
    /**
     * collect discount for invoice
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return $this
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $invoice->setDiscountAmount(0);
        $invoice->setBaseDiscountAmount(0);

        $totalDiscountAmount     = 0;
        $baseTotalDiscountAmount = 0;

        /**
         * Checking if shipping discount was added in previous invoices.
         * So basically if we have invoice with positive discount and it
         * was not canceled we don't add shipping discount to this one.
         */
        $addShippingDicount = true;
        foreach ($invoice->getOrder()->getInvoiceCollection() as $previusInvoice) {
            if ($previusInvoice->getDiscountAmount()) {
                $addShippingDicount = false;
            }
        }

        if ($addShippingDicount) {
            $totalDiscountAmount     = $totalDiscountAmount + $invoice->getOrder()->getShippingDiscountAmount();
            $baseTotalDiscountAmount = $baseTotalDiscountAmount + $invoice->getOrder()->getBaseShippingDiscountAmount();
        }

        $current = $invoice->getOrderCurrencyCode();
        $base = $invoice->getOrder()->getBaseCurrencyCode();
        $round = Mage::getStoreConfig('tax/calculation/round');

        $baseDelta = 0;
        $delta = 0;

        /** @var $item Mage_Sales_Model_Order_Invoice_Item */
        foreach ($invoice->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            if ($orderItem->isDummy()) {
                continue;
            }

            $orderItemDiscount      = (float) $orderItem->getDiscountAmount();
            $baseOrderItemDiscount  = (float) $orderItem->getBaseDiscountAmount();
            $orderItemQty       = $orderItem->getQtyOrdered();

            if ($orderItemDiscount && $orderItemQty) {
                /**
                 * Resolve rounding problems
                 */
                $discount = $orderItemDiscount - $orderItem->getDiscountInvoiced();
                $baseDiscount = $baseOrderItemDiscount - $orderItem->getBaseDiscountInvoiced();
                if (!$item->isLast()) {
                    $activeQty = $orderItemQty - $orderItem->getQtyInvoiced();
                    $discount = $invoice->roundPrice($discount / $activeQty * $item->getQty(), 'regular', true);
                    $baseDiscount = $invoice->roundPrice($baseDiscount / $activeQty * $item->getQty(), 'base', true);
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
                    $discount += floor($delta);
                    $baseDiscount += floor($baseDelta);
                }

                $item->setDiscountAmount($discount);
                $item->setBaseDiscountAmount($baseDiscount);

                $totalDiscountAmount += $discount;
                $baseTotalDiscountAmount += $baseDiscount;
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
                    $invoice->setDiscountAmount(-$totalDiscountAmount);
                    $invoice->setBaseDiscountAmount(-$baseTotalDiscountAmount);
                    break;
                case '7':
                default:
                    $invoice->setDiscountAmount($totalDiscountAmount);
                    $invoice->setBaseDiscountAmount($baseTotalDiscountAmount);
                    break;
            }
        } else {
            switch($_version['minor']) {
                case '14':
                case '13':
                    $invoice->setDiscountAmount(-$totalDiscountAmount);
                    $invoice->setBaseDiscountAmount(-$baseTotalDiscountAmount);
                    break;
                case '12':
                default:
                    $invoice->setDiscountAmount($totalDiscountAmount);
                    $invoice->setBaseDiscountAmount($baseTotalDiscountAmount);
                    break;
            }
        }

        $invoice->setGrandTotal($invoice->getGrandTotal() - $totalDiscountAmount);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() - $baseTotalDiscountAmount);
        return $this;
    }
}