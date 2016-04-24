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
class Rack_Jp_Core_Model_Resource_Customer_Collection extends Mage_Customer_Model_Resource_Customer_Collection
{
    /**
     * add name select sql
     *
     * @return $this|Mage_Customer_Model_Resource_Customer_Collection
     * @throws Mage_Core_Exception
     */
    public function addNameToSelect()
    {
        if (!Mage::getStoreConfig('jpcore/name/enablejp')) {
            return parent::addNameToSelect();
        }
        
        $fields = array();
        $customerAccount = Mage::getConfig()->getFieldset('customer_account');
        foreach ($customerAccount as $code => $node) {
            if ($node->is('name')) {
                $fields[$code] = $code;
            }
        }

        $adapter = $this->getConnection();
        $concatenate = array();
        if (isset($fields['prefix'])) {
            $concatenate[] = $adapter->getCheckSql(
                '{{prefix}} IS NOT NULL AND {{prefix}} != \'\'',
                'LTRIM(RTRIM({{prefix}}))',
                '\'\'');
        }
        $concatenate[] = 'LTRIM(RTRIM({{lastname}}))';
        if (isset($fields['middlename'])) {
            $concatenate[] = $adapter->getCheckSql(
                '{{middlename}} IS NOT NULL AND {{middlename}} != \'\'',
                'LTRIM(RTRIM({{middlename}}))',
                '\'\'');
        }
        $concatenate[] = 'LTRIM(RTRIM({{firstname}}))';
        if (isset($fields['suffix'])) {
            $concatenate[] = $adapter
                ->getCheckSql('{{suffix}} IS NOT NULL AND {{suffix}} != \'\'', "LTRIM(RTRIM({{suffix}}))", "''");
        }

        $nameExpr = $adapter->getConcatSql($concatenate, ' ');

        $this->addExpressionAttributeToSelect('name', $nameExpr, $fields);
        
        return $this;
    }
}