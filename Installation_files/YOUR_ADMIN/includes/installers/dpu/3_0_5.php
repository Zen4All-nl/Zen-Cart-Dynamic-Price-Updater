<?php
/**
 * @package functions
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: mc12345678 thanks to bislewl 6/9/2015
 */
/* 
  V3.0.5, What changed:
    Added JSON response information when retrieving results.
    Incorporated the use of Zen Cart's provided zcJS when it is available.
    Made the DPU class part of the overall system to better support additional observers as needed.
    Rewrote the data collection about a product to improve ability to compare to the active cart.
    Modified quantity reporting to provide quantity already in cart for selected attributes if the quantity is greater than zero.
    Incorporated forum correction for operation when DPU_SHOW_LOADING_IMAGE is set to false.
    Maintained the "default" XML response if JSON is not requested to align with potential historical alterations.
    Added the restoration of the price display if the transaction fails.
    Added jscript function to perform data/screen update/swap.
    Added this additional installer feature to support improved installation/version control.
*/


$zc150 = ((int)PROJECT_VERSION_MAJOR > 1 || (PROJECT_VERSION_MAJOR === '1' && substr(PROJECT_VERSION_MINOR, 0, 3) >= 5));
if ($zc150) { // continue Zen Cart 1.5.0

    $DPUExists = false;

    $old_group_title = "Dynamic Price Updater";
    // Need to update/verify/establish configuration_group info.
    
    $installed = $db->Execute("SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_title = '" . $old_group_title . "'");
    
    if (!$installed->EOF) {
      // The old configuration group exists, so create the new one, add it to the database and establish the configuration_group_id.
      $db->Execute("INSERT INTO " . TABLE_CONFIGURATION_GROUP . " (configuration_group_title, configuration_group_description, sort_order, visible) VALUES ('" . $module_name . " Config', 'Set " . $module_name . " Configuration Options', '1', '1');");
      $configuration_group_id = $db->insert_ID();

      // Set the sort order of the configuration group to be equal to the configuration_group_id, idea being that each new group will be added to the end.
      $db->Execute("UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = " . $configuration_group_id . " WHERE configuration_group_id = " . $configuration_group_id);

      // Need to move all of the old records from here to the other, then delete this old version.
      $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_group_id = " . $configuration_group_id . " WHERE configuration_group_id = " . (int)$installed->fields['configuration_group_id']);
      $db->Execute("DELETE FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_id = " . (int)$installed->fields['configuration_group_id']);
    }

    // Attempt to use the ZC function to test for the existence of the page otherwise detect using SQL.
    if (function_exists('zen_page_key_exists')) 
    {
        $DPUPageExists = zen_page_key_exists('config' . $admin_page);
    } else {
        $DPUPageExists_result = $db->Execute("SELECT FROM " . TABLE_ADMIN_PAGES . " WHERE page_key = 'config" . $admin_page . "' LIMIT 1");
        if ($DPUPageExists_result->EOF && $DPUPageExists_result->RecordCount() === 0) {
            $DPUPageExists = false;
        } else {
            $DPUPageExists = true;
        } 
    }

    if ($DPUPageExists && $configuration_group_id !== (int)$installed->fields['configuration_group_id']) {
      $db->Execute("UPDATE " . TABLE_ADMIN_PAGES . " SET page_params = 'gID=" . (int)$configuration_group_id . "' WHERE page_key = 'config" . $admin_page . "'");
    }

    // Initialize the variable.
    $sort_order = [];
    // Identify the order in which the keys should be added for display.
    $sort_order = [
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
    if (!$DPUPageExists)
    {
        if ((int)$configuration_group_id > 0 /*&& $configuration_group_id_is_new*/) {

            $page_sort_query = "SELECT MAX(sort_order) + 1 as max_sort FROM `". TABLE_ADMIN_PAGES ."` WHERE menu_key='configuration'";
            $page_sort = $db->Execute($page_sort_query);
            $page_sort = $page_sort->fields['max_sort'];

            zen_register_admin_page('config' . $admin_page,
                                'BOX_CONFIGURATION_' . str_replace(' ', '_', strtoupper($module_name)), 
                                'FILENAME_CONFIGURATION',
                                'gID=' . $configuration_group_id, 
                                'configuration', 
                                'Y',
                                $page_sort);
          
            $messageStack->add('Enabled ' . $module_name . ' Configuration Menu.', 'success');
        }

        foreach ($sort_order as $config_key => $config_item) {

            $sql = "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " (configuration_group_id, configuration_key, configuration_title, configuration_value, configuration_description, sort_order, date_added, use_function, set_function) 
              VALUES (:configuration_group_id:, :configuration_key:, :configuration_title:, :configuration_value:, :configuration_description:, :sort_order:, :date_added:, :use_function:, :set_function:)
              ON DUPLICATE KEY UPDATE configuration_group_id = :configuration_group_id:";
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

    } else {

        foreach ($sort_order as $config_key => $config_item) {
	
            $sql = "UPDATE ".TABLE_CONFIGURATION." SET sort_order = :sort_order:, configuration_group_id = :configuration_group_id: WHERE configuration_key = :configuration_key:"; 
            $sql = $db->bindVars($sql, ':sort_order:', ((int)$config_key + 1) * 10, 'integer');
            $sql = $db->bindVars($sql, ':configuration_key:', $config_item['configuration_key']['value'], $config_item['configuration_key']['type']);
            $sql = $db->bindVars($sql, ':configuration_group_id:', $config_item['configuration_group_id']['value'], $config_item['configuration_group_id']['type']);
            $db->Execute($sql);
        }


        $messageStack->add('Updated sort order configuration for ' . $module_name , 'success');
    } // End of New Install
		
} // END OF VERSION 1.5.x INSTALL
