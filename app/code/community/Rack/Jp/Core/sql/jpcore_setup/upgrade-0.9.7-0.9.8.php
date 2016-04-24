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

$installer->startSetup();

$value['customer']['address_templates']['fields']['text']['value'] = '{{depend prefix}}{{var prefix}} {{/depend}}{{var lastname}} {{depend middlename}}{{var middlename}} {{/depend}}{{var firstname}}{{depend suffix}} {{var suffix}}{{/depend}}
{{depend company}}{{var company}}{{/depend}}
{{if postcode}}{{var postcode}},{{/if}}
{{var country}}
{{if region}}{{var region}},{{/if}}
{{if city}}{{var city}},  {{/if}}
{{if street1}}{{var street1}}
{{/if}}
{{depend street2}}{{var street2}}{{/depend}}
{{depend street3}}{{var street3}}{{/depend}}
{{depend street4}}{{var street4}}{{/depend}}
T: {{var telephone}}
{{depend fax}}F: {{var fax}}{{/depend}}
{{depend vat_id}}VAT: {{var vat_id}}{{/depend}}';
$value['customer']['address_templates']['fields']['oneline']['value'] = '{{depend prefix}}{{var prefix}} {{/depend}}{{var lastname}} {{depend middlename}}{{var middlename}} {{/depend}}{{var firstname}}{{depend suffix}} {{var suffix}}{{/depend}}, {{var postcode}}, {{var country}}, {{var region}}, {{var city}}, {{var street}}';
$value['customer']['address_templates']['fields']['html']['value'] = '{{depend prefix}}{{var prefix}} {{/depend}}{{var lastname}} {{depend middlename}}{{var middlename}} {{/depend}}{{var firstname}}{{depend suffix}} {{var suffix}}{{/depend}}<br />
{{depend company}}{{var company}}<br />{{/depend}}
{{if postcode}}{{var postcode}}{{/if}}
{{var country}}
{{if region}}{{var region}}, {{/if}}{{if city}}{{var city}}<br />{{/if}}
{{if street1}}{{var street1}}<br />{{/if}}
{{depend street2}}{{var street2}}<br />{{/depend}}
{{depend street3}}{{var street3}}<br />{{/depend}}
{{depend street4}}{{var street4}}<br />{{/depend}}
{{depend telephone}}T: {{var telephone}}{{/depend}}
{{depend fax}}F: {{var fax}}{{/depend}}
{{depend vat_id}}<br />VAT: {{var vat_id}}{{/depend}}';
$value['customer']['address_templates']['fields']['pdf']['value'] = '{{depend prefix}}{{var prefix}} {{/depend}}{{var lastname}} {{depend middlename}}{{var middlename}} {{/depend}}{{var firstname}}{{depend suffix}} {{var suffix}}{{/depend}}|
{{depend company}}{{var company}}|{{/depend}}
{{if postcode}}{{var postcode}}{{/if}}|
{{var country}}|
{{if region}}{{var region}}, {{/if}}
{{if city}}{{var city}},|{{/if}}
{{if street1}}{{var street1}}
{{/if}}
{{depend street2}}{{var street2}}|{{/depend}}
{{depend street3}}{{var street3}}|{{/depend}}
{{depend street4}}{{var street4}}|{{/depend}}
{{depend telephone}}T: {{var telephone}}{{/depend}}|
{{depend fax}}F: {{var fax}}{{/depend}}|
{{depend vat_id}}VAT: {{var vat_id}}{{/depend}}|';
$value['customer']['address_templates']['fields']['js_template']['value'] = '#{prefix} #{lastname} #{middlename} #{firstname} #{suffix}<br />#{company}<br />#{postcode}<br />#{country_id}<br />#{region}, #{city},<br />#{street0}<br />#{street1}<br />#{street2}<br />#{street3}<br />T: #{telephone}<br />F: #{fax}<br />VAT: #{vat_id}';

Mage::getModel('adminhtml/config_data')
    ->setSection('customer')
    ->setWebsite(null)
    ->setStore(null)
    ->setGroups($value['customer'])
    ->save();

Mage::getConfig()->reinit();
Mage::app()->reinitStores();

$installer->endSetup();