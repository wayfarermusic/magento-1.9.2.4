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
class Rack_Jp_Core_Model_Sales_Quote_Address_Total_Shipping extends Mage_Sales_Model_Quote_Address_Total_Shipping
{
    /**
     * Collect totals information about shipping
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_Sales_Model_Quote_Address_Total_Shipping
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        $oldWeight = $address->getWeight();
        $address->setWeight(0);
        $address->setFreeMethodWeight(0);
        $this->_setAmount(0)
            ->_setBaseAmount(0);

        $items = $this->_getAddressItems($address);
        if (!count($items)) {
            return $this;
        }

        $method     = $address->getShippingMethod();
        $freeAddress= $address->getFreeShipping();

        $addressWeight      = $address->getWeight();
        $freeMethodWeight   = $address->getFreeMethodWeight();

        $addressQty = 0;

        foreach ($items as $item) {
            /**
             * Skip if this item is virtual
             */
            if ($item->getProduct()->isVirtual()) {
                continue;
            }

            /**
             * Children weight we calculate for parent
             */
            if ($item->getParentItem()) {
                continue;
            }

            if ($item->getHasChildren() && $item->isShipSeparately()) {
                foreach ($item->getChildren() as $child) {
                    if ($child->getProduct()->isVirtual()) {
                        continue;
                    }
                    $addressQty += $child->getTotalQty();

                    if (!$item->getProduct()->getWeightType()) {
                        $itemWeight = $child->getWeight();
                        $itemQty    = $child->getTotalQty();
                        $rowWeight  = $itemWeight*$itemQty;
                        $addressWeight += $rowWeight;
                        if ($freeAddress || $child->getFreeShipping()===true) {
                            $rowWeight = 0;
                        } elseif (is_numeric($child->getFreeShipping())) {
                            $freeQty = $child->getFreeShipping();
                            if ($itemQty>$freeQty) {
                                $rowWeight = $itemWeight*($itemQty-$freeQty);
                            }
                            else {
                                $rowWeight = 0;
                            }
                        }
                        $freeMethodWeight += $rowWeight;
                        $item->setRowWeight($rowWeight);
                    }
                }
                if ($item->getProduct()->getWeightType()) {
                    $itemWeight = $item->getWeight();
                    $rowWeight  = $itemWeight*$item->getQty();
                    $addressWeight+= $rowWeight;
                    if ($freeAddress || $item->getFreeShipping()===true) {
                        $rowWeight = 0;
                    } elseif (is_numeric($item->getFreeShipping())) {
                        $freeQty = $item->getFreeShipping();
                        if ($item->getQty()>$freeQty) {
                            $rowWeight = $itemWeight*($item->getQty()-$freeQty);
                        }
                        else {
                            $rowWeight = 0;
                        }
                    }
                    $freeMethodWeight+= $rowWeight;
                    $item->setRowWeight($rowWeight);
                }
            }
            else {
                if (!$item->getProduct()->isVirtual()) {
                    $addressQty += $item->getQty();
                }
                $itemWeight = $item->getWeight();
                $rowWeight  = $itemWeight*$item->getQty();
                $addressWeight+= $rowWeight;
                if ($freeAddress || $item->getFreeShipping()===true) {
                    $rowWeight = 0;
                } elseif (is_numeric($item->getFreeShipping())) {
                    $freeQty = $item->getFreeShipping();
                    if ($item->getQty()>$freeQty) {
                        $rowWeight = $itemWeight*($item->getQty()-$freeQty);
                    }
                    else {
                        $rowWeight = 0;
                    }
                }
                $freeMethodWeight+= $rowWeight;
                $item->setRowWeight($rowWeight);
            }
        }

        if (isset($addressQty)) {
            $address->setItemQty($addressQty);
        }

        $address->setWeight($addressWeight);
        $address->setFreeMethodWeight($freeMethodWeight);

        $address->collectShippingRates();

        $this->_setAmount(0)
            ->_setBaseAmount(0);

        $method = $address->getShippingMethod();
        $current = $address->getQuote()->getQuoteCurrencyCode();
        $round = Mage::getStoreConfig('tax/calculation/round');

        if ($method) {
            foreach ($address->getAllShippingRates() as $rate) {
                if ($rate->getCode()==$method) {
                    $amountPrice = $address->getQuote()->getStore()->convertPrice($rate->getPrice(), false);

                    if(Mage::helper('jpcore')->canRemoveDecimal($current)) {
                        switch($round) {
                            case 'ceil':
                                $amountPrice = ceil($amountPrice);
                                break;
                            case 'floor':
                                $amountPrice = floor($amountPrice);
                                break;
                            case 'round':
                                $amountPrice = round($amountPrice);
                                break;
                        }
                    }

                    $this->_setAmount($amountPrice);
                    $this->_setBaseAmount($rate->getPrice());
                    $shippingDescription = $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle();
                    $address->setShippingDescription(trim($shippingDescription, ' -'));
                    break;
                }
            }
        }

        return $this;
    }
}