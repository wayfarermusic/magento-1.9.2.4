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
class Rack_Jp_Core_Model_Sales_Order_Creditmemo_Total_Subtotal extends Mage_Sales_Model_Order_Creditmemo_Total_Subtotal
{
    /**
     * Collect Creditmemo subtotal
     *
     * @param   Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @return  Mage_Sales_Model_Order_Creditmemo_Total_Subtotal
     */
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $subtotal       = 0;
        $baseSubtotal   = 0;
        $subtotalInclTax= 0;
        $baseSubtotalInclTax = 0;

        foreach ($creditmemo->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy()) {
                continue;
            }

            $item->calcRowTotal();

            $subtotal       += $item->getRowTotal();
            $baseSubtotal   += $item->getBaseRowTotal();
            $subtotalInclTax+= $item->getRowTotalInclTax();
            $baseSubtotalInclTax += $item->getBaseRowTotalInclTax();
        }

        $current = $creditmemo->getOrderCurrencyCode();
        $base = $creditmemo->getOrder()->getBaseCurrencyCode();
        $round = Mage::getStoreConfig('tax/calculation/round');

        if(Mage::helper('jpcore')->canRemoveDecimal($current)) {
            switch($round) {
                case 'ceil':
                    $subtotal = ceil($subtotal);
                    $subtotalInclTax = ceil($subtotalInclTax);
                    break;
                case 'floor':
                    $subtotal = floor($subtotal);
                    $subtotalInclTax = floor($subtotalInclTax);
                    break;
            }
        }

        if(Mage::helper('jpcore')->canRemoveDecimal($base)) {
            switch($round) {
                case 'ceil':
                    $baseSubtotal = ceil($baseSubtotal);
                    $baseSubtotalInclTax = ceil($baseSubtotalInclTax);
                    break;
                case 'floor':
                    $baseSubtotal = floor($baseSubtotal);
                    $baseSubtotalInclTax = floor($baseSubtotalInclTax);
                    break;
            }
        }

        $creditmemo->setSubtotal($subtotal);
        $creditmemo->setBaseSubtotal($baseSubtotal);
        $creditmemo->setSubtotalInclTax($subtotalInclTax );
        $creditmemo->setBaseSubtotalInclTax($baseSubtotalInclTax);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $subtotal);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseSubtotal);

        return $this;
    }
}
