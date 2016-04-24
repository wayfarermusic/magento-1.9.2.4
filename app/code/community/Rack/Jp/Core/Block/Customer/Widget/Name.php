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
class Rack_Jp_Core_Block_Customer_Widget_Name extends Mage_Customer_Block_Widget_Name
{
    /**
     * constructor. switch tempalte depends on config value.
     */
    public function _construct()
    {
        parent::_construct();

        // default template location
        if(Mage::getStoreConfig('jpcore/name/enablejp')){
            $this->setTemplate('jpcore/customer/widget/name_jp.phtml');
        } else {
            $this->setTemplate('jpcore/customer/widget/name_en.phtml');
        }
    }
}