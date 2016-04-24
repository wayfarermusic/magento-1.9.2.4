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
class Rack_Jp_Core_Model_Resource_Order_Shipment extends Mage_Sales_Model_Resource_Order_Shipment
{
    /**
     * init virtual grid columns
     *
     * @return $this|Mage_Sales_Model_Resource_Order_Shipment
     */
    protected function _initVirtualGridColumns()
    {
        if (!Mage::getStoreConfig('jpcore/name/enablejp')) {
            return parent::_initVirtualGridColumns();
        }
        
        $adapter          = $this->_getReadAdapter();
        $checkedFirstname = $adapter->getIfNullSql('{{table}}.firstname', $adapter->quote(''));
        $checkedLastname  = $adapter->getIfNullSql('{{table}}.lastname', $adapter->quote(''));
        
        $this->addVirtualGridColumn(
            'shipping_name',
            'sales/order_address',
            array('billing_address_id' => 'entity_id'),
            $adapter->getConcatSql(array($checkedLastname, $adapter->quote(' '), $checkedFirstname))
        )
        ->addVirtualGridColumn(
            'order_increment_id',
            'sales/order',
            array('order_id' => 'entity_id'),
            'increment_id'
        )
        ->addVirtualGridColumn(
            'order_created_at',
            'sales/order',
            array('order_id' => 'entity_id'),
            'created_at'
        );

        return $this;
    }
}