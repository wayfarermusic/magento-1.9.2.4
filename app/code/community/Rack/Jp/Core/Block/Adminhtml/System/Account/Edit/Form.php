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
class Rack_Jp_Core_Block_Adminhtml_System_Account_Edit_Form extends Mage_Adminhtml_Block_System_Account_Edit_Form
{
    /**
     * prepare form
     *
     * @return $this|Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        if (Mage::app()->getLocale()->getLocaleCode() != 'ja_JP') {
            return parent::_prepareForm();;
        }
        $userId = Mage::getSingleton('admin/session')->getUser()->getId();
        $user = Mage::getModel('admin/user')
            ->load($userId);
        $user->unsetData('password');

        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('adminhtml')->__('Account Information')));

        $fieldset->addField('username', 'text', array(
                'name'  => 'username',
                'label' => Mage::helper('adminhtml')->__('User Name'),
                'title' => Mage::helper('adminhtml')->__('User Name'),
                'required' => true,
            )
        );

        $fieldset->addField('lastname', 'text', array(
                'name'  => 'lastname',
                'label' => Mage::helper('adminhtml')->__('Last Name'),
                'title' => Mage::helper('adminhtml')->__('Last Name'),
                'required' => true,
            )
        );

        $fieldset->addField('firstname', 'text', array(
                'name'  => 'firstname',
                'label' => Mage::helper('adminhtml')->__('First Name'),
                'title' => Mage::helper('adminhtml')->__('First Name'),
                'required' => true,
            )
        );

        $fieldset->addField('user_id', 'hidden', array(
                'name'  => 'user_id',
            )
        );

        $fieldset->addField('email', 'text', array(
                'name'  => 'email',
                'label' => Mage::helper('adminhtml')->__('Email'),
                'title' => Mage::helper('adminhtml')->__('User Email'),
                'required' => true,
            )
        );

        $_edition = Mage::getEdition();
        $_version = Mage::getVersionInfo();
        $_showCurrentPassword = false;

        if($_edition == Mage::EDITION_COMMUNITY) {
            if(($_version['minor'] == '9' && $_version['revision'] >= '2') || $_version['minor'] > '9') {
                $_showCurrentPassword = true;
            }
        } else {
            if(($_version['minor'] == '14' && $_version['revision'] >= '2') || $_version['minor'] > '14') {
                $_showCurrentPassword = true;
            }
        }

        if($_showCurrentPassword) {
            $fieldset->addField('current_password', 'obscure', array(
                    'name'  => 'current_password',
                    'label' => Mage::helper('adminhtml')->__('Current Admin Password'),
                    'title' => Mage::helper('adminhtml')->__('Current Admin Password'),
                    'required' => true,
                )
            );
        }

        $fieldset->addField('password', 'password', array(
                'name'  => 'new_password',
                'label' => Mage::helper('adminhtml')->__('New Password'),
                'title' => Mage::helper('adminhtml')->__('New Password'),
                'class' => 'input-text validate-admin-password',
            )
        );

        $fieldset->addField('confirmation', 'password', array(
                'name'  => 'password_confirmation',
                'label' => Mage::helper('adminhtml')->__('Password Confirmation'),
                'class' => 'input-text validate-cpassword',
            )
        );

        $form->setValues($user->getData());
        $form->setAction($this->getUrl('*/system_account/save'));
        $form->setMethod('post');
        $form->setUseContainer(true);
        $form->setId('edit_form');

        $this->setForm($form);

        return $this;
    }
}