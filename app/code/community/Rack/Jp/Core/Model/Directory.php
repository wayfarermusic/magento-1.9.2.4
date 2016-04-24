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
class Rack_Jp_Core_Model_Directory extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        $this->_init('jpcore/directory');
    }
    
    protected function _afterSave()
    {
        if ($this->hasData('store_labels')) {
            $labelCollection = Mage::getResourceModel('jpcore/directory_label_collection');

            $labelCollection->addCurrencyIdToFilter($this->getId());
            //remove current labels
            foreach ($labelCollection->getItems() as $label) {
                $label->delete();
            }
            
            $labelCollection->resetData();
            //save new labels
            $labels = $this->getData('store_labels');
            foreach ($labels as $storeId => $label) {
                if (empty($label)) {
                    continue;
                }
                $labelObj = Mage::getModel('jpcore/directory_label');
                $labelObj->setData(array(
                    'currency_id' => $this->getId(),
                    'store_id'        => $storeId,
                    'label'           => $label,
                ));
                $labelCollection->addItem($labelObj);
            }
            
            $labelCollection->save();
        }
        parent::_afterSave();
    }
    
    public function loadLabels()
    {
        if (!$this->getId()) {
            return;
        }
        $labelCollection = Mage::getResourceModel('jpcore/currency_label_collection');
        
        $labelCollection->addCurrencyIdToFilter($this->getId());
        $labels = array();
        foreach ($labelCollection->getItems() as $labelObj) {
            $labels[$labelObj->getStoreId()] = $labelObj->getLabel();
        }
        
        if ($labels) {
            $this->setData('store_labels', $labels);
        }
        $storeId = Mage::app()->getStore()->getId();
        if (isset($labels[$storeId])) {
            $this->setLabel($labels[$storeId]);
        } else {
            $this->setLabel($labels[0]);
        }
    }
}