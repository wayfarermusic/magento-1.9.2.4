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
$installer = $this;

$eavConfig = Mage::getSingleton('eav/config');
Mage::app()->reinitStores();
$websites  = Mage::app()->getWebsites(false);

$scopes = array('customer', 'customer_address');
$attributes = array('firstnamekana', 'lastnamekana');
$form_code = array(
                                'checkout_register',
                                'customer_account_edit',
                                'customer_account_create',
                                'adminhtml_customer',
                                'customer_address_edit'
                                );
$form_code_address = array('adminhtml_customer_address','customer_register_address',);

foreach ($websites as $website) {
    $store = $website->getDefaultStore();
    if (!$store) {
        continue;
    }
    foreach($scopes as $scope) {
        foreach($attributes as $attribute) {
                $_attribute = $eavConfig->getAttribute($scope, $attribute);
                
                if ($scope == 'customer_address') {
                    $_attribute->setData('used_in_forms', array_merge($form_code, $form_code_address));
                } else {
                    $_attribute->setData('used_in_forms', $form_code);
                }
                $_attribute->setWebsite($website);
                $_attribute->save();
         }
    }
    
}
$installer->endSetup();