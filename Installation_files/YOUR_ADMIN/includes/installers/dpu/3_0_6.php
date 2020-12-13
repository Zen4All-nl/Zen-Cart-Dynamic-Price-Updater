<?php
/**
 * @package functions
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: mc12345678 thanks to bislewl 6/9/2015
 */
/* 
  V3.0.6, What changed:
    Modified/updated the class information to provide the sidebox data.
    Correct additional javascript errors to prevent having to catch them.
    Modified sidebox to not display/be active if the page's javascript for dpu is not active.
    Added additional type casting to force data to the expected type.
    Incorporate the tax calculation correction posted in the forum into the code.
    Removed the use of default parameters because some browsers do/did not support it.
    Add separate control for update checking of this plugin.
    Updated installer version check to ZC 1.5.5e version.
    Corrected installer code to validate new version exists.
*/


$zc150 = ((int)PROJECT_VERSION_MAJOR > 1 || (PROJECT_VERSION_MAJOR === '1' && substr(PROJECT_VERSION_MINOR, 0, 3) >= 5));
if ($zc150) { // continue Zen Cart 1.5.0

/*    $DPUExists = FALSE;

    // Attempt to use the ZC function to test for the existence of the page otherwise detect using SQL.
    if (function_exists('zen_page_key_exists')) 
    {
        $DPUPageExists = zen_page_key_exists('config' . $admin_page);
    } else {
        $DPUPageExists_result = $db->Execute("SELECT FROM " . TABLE_ADMIN_PAGES . " WHERE page_key = 'config" . $admin_page . "' LIMIT 1");
        if ($DPUPageExists_result->EOF && $DPUPageExists_result->RecordCount() == 0) {
        } else {
            $DPUPageExists = TRUE;
        } 
    }*/

    // Initialize the variable.
    $sort_order = [];

/*    $sort_order_sql = "SELECT MAX(sort_order) as max_sort FROM `". TABLE_CONFIGURATION ."` WHERE configuration_group_id=" . (int)$configuration_group_id;
    
    $sort_order_result = $db->Execute($sort_order_sql);
    
    $sort_order_start = (int)($sort_order_result->fields['max_sort'] / 10);*/

    // Identify the order in which the keys should be added for display.
    // Inserted a new configuration and wanted to keep things in line.
    $sort_order =     $sort_order = [
                ['configuration_group_id' => ['value' => $configuration_group_id,
                                                   'type' => 'integer'],
                      'configuration_key' => ['value' => 'DPU_STATUS',
                                                   'type' => 'string'],
                      'configuration_title' => ['value' => 'Dynamic Price Updater Status',
                                                   'type' => 'string'],
                      'configuration_value' => ['value' => 'false',
                                                   'type' => 'string'],
                      'configuration_description' => ['value' => 'Enable Dynamic Price Updater?',
                                                   'type' => 'string'],
                      'date_added' => ['value' => 'NOW()',
                                                   'type' => 'noquotestring'],
                      'use_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                      'set_function' => ['value' => 'zen_cfg_select_option(array(\'true\', \'false\'),',
                                                   'type' => 'string'],
                ],
                ['configuration_group_id' => ['value' => $configuration_group_id,
                                                   'type' => 'integer'],
                      'configuration_key' => ['value' => $module_constant . '_VERSION',
                                                   'type' => 'string'],
                      'configuration_title' => ['value' => 'Dynamic Price Updater Version',
                                                   'type' => 'string'],
                      'configuration_value' => ['value' => '3.0.5',
                                                   'type' => 'string'],
                      'configuration_description' => ['value' => 'Dynamic Price Updater version',
                                                   'type' => 'string'],
                      'date_added' => ['value' => 'NOW()',
                                                   'type' => 'noquotestring'],
                      'use_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                      'set_function' => ['value' => 'trim(',
                                                   'type' => 'string'],
                ],
                ['configuration_group_id' => ['value' => $configuration_group_id,
                                                   'type' => 'integer'],
                      'configuration_key' => ['value' => $module_constant . '_PLUGIN_CHECK',
                                                   'type' => 'string'],
                      'configuration_title' => ['value' => 'Dynamic Price Updater Version Check?',
                                                   'type' => 'string'],
                      'configuration_value' => ['value' => 'true',
                                                   'type' => 'string'],
                      'configuration_description' => ['value' => 'Enable DPU to be able to check the ZC site for an updated copy.  Default is true.',
                                                   'type' => 'string'],
                      'date_added' => ['value' => 'NOW()',
                                                   'type' => 'noquotestring'],
                      'use_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                      'set_function' => ['value' => 'zen_cfg_select_option(array(\'true\', \'false\'),',
                                                   'type' => 'string'],
                ],
                ['configuration_group_id' => ['value' => $configuration_group_id,
                                                   'type' => 'integer'],
                      'configuration_key' => ['value' => 'DPU_PRICE_ELEMENT_ID',
                                                   'type' => 'string'],
                      'configuration_title' => ['value' => 'Where to display the price',
                                                   'type' => 'string'],
                      'configuration_value' => ['value' => 'productPrices',
                                                   'type' => 'string'],
                      'configuration_description' => ['value' => 'This is the ID of the element where your price is displayed.<br /><strong>default => productPrices</strong>',
                                                   'type' => 'string'],
                      'date_added' => ['value' => 'NOW()',
                                                   'type' => 'noquotestring'],
                      'use_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                      'set_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                ],
                ['configuration_group_id' => ['value' => $configuration_group_id,
                                                   'type' => 'integer'],
                      'configuration_key' => ['value' => 'DPU_PRODUCT_FORM',
                                                   'type' => 'string'],
                      'configuration_title' => ['value' => 'Define used to set a variable for this script',
                                                   'type' => 'string'],
                      'configuration_value' => ['value' => 'cart_quantity',
                                                   'type' => 'string'],
                      'configuration_description' => ['value' => 'This should never change<br /><strong>default => cart_quantity</strong>',
                                                   'type' => 'string'],
                      'date_added' => ['value' => 'NOW()',
                                                   'type' => 'noquotestring'],
                      'use_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                      'set_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                ],
                ['configuration_group_id' => ['value' => $configuration_group_id,
                                                   'type' => 'integer'],
                      'configuration_key' => ['value' => 'DPU_WEIGHT_ELEMENT_ID',
                                                   'type' => 'string'],
                      'configuration_title' => ['value' => 'Where to display the weight',
                                                   'type' => 'string'],
                      'configuration_value' => ['value' => 'productWeight',
                                                   'type' => 'string'],
                      'configuration_description' => ['value' => 'This is the ID where your weight is displayed.<br /><strong>default => productWeight</strong>',
                                                   'type' => 'string'],
                      'date_added' => ['value' => 'NOW()',
                                                   'type' => 'noquotestring'],
                      'use_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                      'set_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                ],
                ['configuration_group_id' => ['value' => $configuration_group_id,
                                                   'type' => 'integer'],
                      'configuration_key' => ['value' => 'DPU_SHOW_LOADING_IMAGE',
                                                   'type' => 'string'],
                      'configuration_title' => ['value' => 'show a small loading graphic',
                                                   'type' => 'string'],
                      'configuration_value' => ['value' => 'true',
                                                   'type' => 'string'],
                      'configuration_description' => ['value' => 'true to show a small loading graphic so the user knows something is happening',
                                                   'type' => 'string'],
                      'date_added' => ['value' => 'NOW()',
                                                   'type' => 'noquotestring'],
                      'use_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                      'set_function' => ['value' => 'zen_cfg_select_option(array(\'true\', \'false\'),',
                                                   'type' => 'string'],
                ],
                ['configuration_group_id' => ['value' => $configuration_group_id,
                                                   'type' => 'integer'],
                      'configuration_key' => ['value' => 'DPU_SHOW_CURRENCY_SYMBOLS',
                                                   'type' => 'string'],
                      'configuration_title' => ['value' => 'Show currency symbols',
                                                   'type' => 'string'],
                      'configuration_value' => ['value' => 'true',
                                                   'type' => 'string'],
                      'configuration_description' => ['value' => '',
                                                   'type' => 'string'],
                      'date_added' => ['value' => 'NOW()',
                                                   'type' => 'noquotestring'],
                      'use_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                      'set_function' => ['value' => 'zen_cfg_select_option(array(\'true\', \'false\'),',
                                                   'type' => 'string'],
                ],
                ['configuration_group_id' => ['value' => $configuration_group_id,
                                                   'type' => 'integer'],
                      'configuration_key' => ['value' => 'DPU_SHOW_QUANTITY',
                                                   'type' => 'string'],
                      'configuration_title' => ['value' => 'Show product quantity',
                                                   'type' => 'string'],
                      'configuration_value' => ['value' => 'false',
                                                   'type' => 'string'],
                      'configuration_description' => ['value' => '',
                                                   'type' => 'string'],
                      'date_added' => ['value' => 'NOW()',
                                                   'type' => 'noquotestring'],
                      'use_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                      'set_function' => ['value' => 'zen_cfg_select_option(array(\'true\', \'false\'),',
                                                   'type' => 'string'],
                ],
                ['configuration_group_id' => ['value' => $configuration_group_id,
                                                   'type' => 'integer'],
                      'configuration_key' => ['value' => 'DPU_SECOND_PRICE',
                                                   'type' => 'string'],
                      'configuration_title' => ['value' => 'Where to display the second price',
                                                   'type' => 'string'],
                      'configuration_value' => ['value' => 'cartAdd',
                                                   'type' => 'string'],
                      'configuration_description' => ['value' => '',
                                                   'type' => 'string'],
                      'date_added' => ['value' => 'NOW()',
                                                   'type' => 'noquotestring'],
                      'use_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                      'set_function' => ['value' => 'NULL',
                                                   'type' => 'noquotestring'],
                ],
    ];


    // if the admin page is not installed, then insert it using either the ZC function or straight SQL.

    foreach ($sort_order as $config_key => $config_item) {

        $sql = "INSERT INTO " . TABLE_CONFIGURATION . " (configuration_group_id, configuration_key, configuration_title, configuration_value, configuration_description, sort_order, date_added, use_function, set_function) 
          VALUES (:configuration_group_id:, :configuration_key:, :configuration_title:, :configuration_value:, :configuration_description:, :sort_order:, :date_added:, :use_function:, :set_function:)
          ON DUPLICATE KEY UPDATE sort_order = :sort_order:";
        $sql = $db->bindVars($sql, ':configuration_group_id:', $config_item['configuration_group_id']['value'], $config_item['configuration_group_id']['type']);
        $sql = $db->bindVars($sql, ':configuration_key:', $config_item['configuration_key']['value'], $config_item['configuration_key']['type']);
        $sql = $db->bindVars($sql, ':configuration_title:', $config_item['configuration_title']['value'], $config_item['configuration_title']['type']);
        $sql = $db->bindVars($sql, ':configuration_value:', $config_item['configuration_value']['value'], $config_item['configuration_value']['type']);
        $sql = $db->bindVars($sql, ':configuration_description:', $config_item['configuration_description']['value'], $config_item['configuration_description']['type']);
        $sql = $db->bindVars($sql, ':sort_order:', ((int)$config_key + 1) * 10, 'integer');
        $sql = $db->bindVars($sql, ':date_added:', $config_item['date_added']['value'], $config_item['date_added']['type']);
        $sql = $db->bindVars($sql, ':use_function:', $config_item['use_function']['value'], $config_item['use_function']['type']);
        $sql = $db->bindVars($sql, ':set_function:', $config_item['set_function']['value'], $config_item['set_function']['type']);
        $db->Execute($sql);
    }

    $messageStack->add('Inserted configuration for ' . $module_name , 'success');

} // END OF VERSION 1.5.x INSTALL
