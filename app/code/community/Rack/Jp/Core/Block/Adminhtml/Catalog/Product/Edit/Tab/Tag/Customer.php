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
class Rack_Jp_Core_Block_Adminhtml_Catalog_Product_Edit_Tab_Tag_Customer extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Tag_Customer
{
    /**
     * Prepare Grid Columns
     *
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        if (Mage::app()->getLocale()->getLocaleCode() == 'ja_JP') {
            $this->addColumn('lastname', array(
                'header'        => Mage::helper('catalog')->__('Last Name'),
                'index'         => 'lastname',
            ));
            
            $this->addColumn('firstname', array(
            'header'    => Mage::helper('catalog')->__('First Name'),
            'index'     => 'firstname',
            ));

            $this->addColumn('email', array(
                'header'        => Mage::helper('catalog')->__('Email'),
                'index'         => 'email',
            ));

            $this->addColumn('name', array(
                'header'        => Mage::helper('catalog')->__('Tag Name'),
                'index'         => 'name',
            ));
            
            $this->sortColumnsByOrder();
            
            return $this;
        } else {
            return parent::_prepareColumns();
        }
    }
}