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
$installer->addAttribute(
    'customer',
    'firstnamekana',
    array(
        'label' => 'First Name Kana',
        'is_required' => '0'
    )
);
$installer->addAttribute(
    'customer',
    'lastnamekana',
    array(
        'label'         => 'Last Name Kana',
        'is_required' => '0'
    )
);
$installer->addAttribute(
    'customer_address',
    'firstnamekana',
    array(
        'label' => 'First Name Kana',
        'is_required' => '0'
    )
);
$installer->addAttribute(
    'customer_address',
    'lastnamekana',
    array(
        'label' => 'Last Name Kana',
        'is_required' => '0'
    )
);
$installer->addAttribute(
    'quote',
    'customer_firstnamekana',
    array(
        'visible'=> false
    )
);
$installer->addAttribute(
    'quote',
    'customer_lastnamekana',
    array(
        'visible'=> false
    )
);
$installer->addAttribute(
    'quote_address',
    'firstnamekana',
    array()
);
$installer->addAttribute(
    'quote_address',
    'lastnamekana',
    array()
);
$installer->addAttribute(
    'order',
    'customer_firstnamekana',
    array(
        'visible'=> false
    )
);
$installer->addAttribute(
    'order',
    'customer_lastnamekana',
    array(
        'visible'=> false
    )
);
$installer->addAttribute(
    'order_address',
    'firstnamekana',
    array()
);
$installer->addAttribute(
    'order_address',
    'lastnamekana',
    array()
);


$directory_country_region = $installer->getTable('directory/country_region');
$installer->run("
INSERT INTO `{$directory_country_region}` (country_id, code, default_name) VALUES 
('JP', 'Hokkaido', 'Hokkaido'),
('JP', 'Aomori', 'Aomori'),
('JP', 'Iwate', 'Iwate'),
('JP', 'Miyagi', 'Miyagi'),
('JP', 'Akita', 'Akita'),
('JP', 'Yamagata', 'Yamagata'),
('JP', 'Fukushima', 'Fukushima'),
('JP', 'Ibaraki', 'Ibaraki'),
('JP', 'Tochigi', 'Tochigi'),
('JP', 'Gunma', 'Gunma'),
('JP', 'Saitama', 'Saitama'),
('JP', 'Chiba', 'Chiba'),
('JP', 'Tokyo', 'Tokyo'),
('JP', 'Kanagawa', 'Kanagawa'),
('JP', 'Niigata', 'Niigata'),
('JP', 'Toyama', 'Toyama'),
('JP', 'Ishikawa', 'Ishikawa'),
('JP', 'Fukui', 'Fukui'),
('JP', 'Yamanashi', 'Yamanashi'),
('JP', 'Nagano', 'Nagano'),
('JP', 'Gifu', 'Gifu'),
('JP', 'Shizuoka', 'Shizuoka'),
('JP', 'Aichi', 'Aichi'),
('JP', 'Mie', 'Mie'),
('JP', 'Shiga', 'Shiga'),
('JP', 'Kyoto', 'Kyoto'),
('JP', 'Osaka', 'Osaka'),
('JP', 'Hyogo', 'Hyogo'),
('JP', 'Nara', 'Nara'),
('JP', 'Wakayama', 'Wakayama'),
('JP', 'Tottori', 'Tottori'),
('JP', 'Shimane', 'Shimane'),
('JP', 'Okayama', 'Okayama'),
('JP', 'Hiroshima', 'Hiroshima'),
('JP', 'Yamaguchi', 'Yamaguchi'),
('JP', 'Tokushima', 'Tokushima'),
('JP', 'Kagawa', 'Kagawa'),
('JP', 'Ehime', 'Ehime'),
('JP', 'Kochi', 'Kochi'),
('JP', 'Fukuoka', 'Fukuoka'),
('JP', 'Saga', 'Saga'),
('JP', 'Nagasaki', 'Nagasaki'),
('JP', 'Kumamoto', 'Kumamoto'),
('JP', 'Oita', 'Oita'),
('JP', 'Miyazaki', 'Miyazaki'),
('JP', 'Kagoshima', 'Kagoshima'),
('JP', 'Okinawa', 'Okinawa');
");
$directory_country_region_name_table = $installer->getTable('directory/country_region_name');
$installer->run("
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'北海道' FROM {$directory_country_region} AS a WHERE a.default_name = 'Hokkaido';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'青森県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Aomori';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'岩手県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Iwate';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'宮城県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Miyagi';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'秋田県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Akita';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'山形県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Yamagata';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'福島県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Fukushima';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'茨城県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Ibaraki';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'栃木県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Tochigi';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'群馬県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Gunma';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'埼玉県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Saitama';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'千葉県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Chiba';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'東京都' FROM {$directory_country_region} AS a WHERE a.default_name = 'Tokyo';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'神奈川県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Kanagawa';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'新潟県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Niigata';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'富山県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Toyama';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'石川県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Ishikawa';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'福井県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Fukui';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'山梨県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Yamanashi';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'長野県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Nagano';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'岐阜県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Gifu';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'静岡県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Shizuoka';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'愛知県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Aichi';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'三重県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Mie';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'滋賀県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Shiga';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'京都府' FROM {$directory_country_region} AS a WHERE a.default_name = 'Kyoto';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'大阪府' FROM {$directory_country_region} AS a WHERE a.default_name = 'Osaka';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'兵庫県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Hyogo';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'奈良県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Nara';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'和歌山県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Wakayama';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'鳥取県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Tottori';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'島根県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Shimane';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'岡山県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Okayama';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'広島県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Hiroshima';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'山口県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Yamaguchi';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'徳島県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Tokushima';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'香川県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Kagawa';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'愛媛県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Ehime';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'高知県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Kochi';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'福岡県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Fukuoka';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'佐賀県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Saga';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'長崎県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Nagasaki';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'熊本県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Kumamoto';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'大分県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Oita';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'宮崎県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Miyazaki';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'鹿児島県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Kagoshima';
INSERT INTO `{$directory_country_region_name_table}` (locale,region_id,name) SELECT 'ja_JP',a.region_id,'沖縄県' FROM {$directory_country_region} AS a WHERE a.default_name = 'Okinawa';
");

$installer->endSetup();