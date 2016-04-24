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
class Rack_Jp_Core_Test_Model_Store extends EcomDev_PHPUnit_Test_Case
{
    protected $_store = null;

    public function setUp()
    {
        $this->_store = Mage::app()->getStore();
    }

    public function testSetCurrencyAsJpy()
    {
        $value['options']['fields']['allow']['value'] = array('JPY','USD');
        $value['options']['fields']['base']['value']  = 'JPY';
        $value['options']['fields']['display']['value']  = 'JPY';

        Mage::getModel('adminhtml/config_data')
            ->setSection('currency')
            ->setWebsite(null)
            ->setStore(null)
            ->setGroups($value)
            ->save();
        Mage::getConfig()->reinit();

        $importModel = Mage::getModel(
            Mage::getConfig()->getNode('global/currency/import/services/webservicex/model')->asArray()
        );
        $rates = $importModel->fetchRates();

        foreach ($rates as $currencyCode => $rate) {
            foreach( $rate as $currencyTo => $value ) {
                $value = abs(Mage::getSingleton('core/locale')->getNumber($value));
                $data[$currencyCode][$currencyTo] = $value;
            }
        }

        Mage::getModel('directory/currency')->saveRates($data);
    }

    /**
     * Test for ceiling price. JPY base and USD base.
     */
    public function testCeilPrice()
    {

    }

    public function testFloorPrice()
    {

    }

    public function testRoundPrice()
    {

    }



}