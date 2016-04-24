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
class Rack_Jp_Core_Block_Adminhtml_Permissions_Role_Grid_User extends Mage_Adminhtml_Block_Permissions_Role_Grid_User
{
    /**
     * Prepare columns
     *
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        if (Mage::app()->getLocale()->getLocaleCode() != 'ja_JP') {
            return $this;
        }
        $this->_columns = array();
        $this->addColumn('in_role_users', array(
            'header_css_class' => 'a-center',
            'type'      => 'checkbox',
            'name'      => 'in_role_users',
            'values'    => $this->_getUsers(),
            'align'     => 'center',
            'index'     => 'user_id'
        ));

        $this->addColumn('role_user_id', array(
            'header'    =>Mage::helper('adminhtml')->__('User ID'),
            'width'     =>5,
            'align'     =>'left',
            'sortable'  =>true,
            'index'     =>'user_id'
        ));

        $this->addColumn('role_user_username', array(
            'header'    =>Mage::helper('adminhtml')->__('User Name'),
            'align'     =>'left',
            'index'     =>'username'
        ));

        $this->addColumn('role_user_lastname', array(
            'header'    =>Mage::helper('adminhtml')->__('Last Name'),
            'align'     =>'left',
            'index'     =>'lastname'
        ));

        $this->addColumn('role_user_firstname', array(
            'header'    =>Mage::helper('adminhtml')->__('First Name'),
            'align'     =>'left',
            'index'     =>'firstname'
        ));

        $this->addColumn('role_user_email', array(
            'header'    =>Mage::helper('adminhtml')->__('Email'),
            'width'     =>40,
            'align'     =>'left',
            'index'     =>'email'
        ));

        $this->addColumn('role_user_is_active', array(
            'header'    => Mage::helper('adminhtml')->__('Status'),
            'index'     => 'is_active',
            'align'     =>'left',
            'type'      => 'options',
            'options'   => array('1' => Mage::helper('adminhtml')->__('Active'), '0' => Mage::helper('adminhtml')->__('Inactive')),
        ));
        return $this;
    }
}