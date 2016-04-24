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
class Rack_Jp_Core_Model_Resource_Directory_Region_Collection extends Mage_Directory_Model_Resource_Region_Collection
{
    /**
     * constructor switch region sort order depends on config value
     */
    protected function _construct()
    {
        $this->_init('directory/region');
        
        $this->_countryTable    = $this->getTable('directory/country');
        $this->_regionNameTable = $this->getTable('directory/country_region_name');

        if(Mage::getModel('core/locale')->getLocaleCode() !== 'ja_JP') {
            $this->addOrder('name', Varien_Data_Collection::SORT_ORDER_ASC);
            $this->addOrder('default_name', Varien_Data_Collection::SORT_ORDER_ASC);
        }
    }

    /**
     * Convert collection items to select options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        if(Mage::getModel('core/locale')->getLocaleCode() !== 'ja_JP') {
            $options = $this->_toOptionArray('region_id', 'default_name', array('title' => 'default_name'));
        } else {
            $options = $this->_toOptionArray('region_id', 'name', array('title' => 'default_name'));
        }
        if (count($options) > 0) {
            array_unshift($options, array(
                'title '=> null,
                'value' => '0',
                'label' => Mage::helper('directory')->__('-- Please select --')
            ));
        }
        return $options;
    }
}