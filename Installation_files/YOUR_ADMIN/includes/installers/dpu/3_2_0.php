<?php
/**
 * @package functions Dynamic Price Updater
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: mc12345678 thanks to bislewl 6/9/2015
 */

/*
  V3.2.0, What changed:
  - Identified this version as 3.2.0 because 1) this update provided significant new functionality and modified/provided
      new database features and 2) 3.1.0 had previously been identified in the GitHub path/history and did not want to
      cause undo confusion.
  - Commented out code that performed no operation/action.
  - Added an update to the customer's latest page when using an ajax style call to improve display of
      information on the who's online admin page.
  - Added a method to update price text to represent when none and less than all attributes have been selected.
  - Added DPU_ATTRIBUTES_MULTI_PRICE_TEXT to be able to control which of the pretext(s) to allow to show.
  - Added a variable to be able to update/display the text before the first price when price includes
      a special and/or a sale.
  - Added ability to update what is known as the normalprice which is the price/value that is crossed out when there
      is a sale or other special.
  - Added stock quantity update capability which incorporates the possibility of operating with ZC 1.5.x and
      control how the information is replaces other content.  Guidance for display modifications provided in readme.txt.
  - Corrected use of float casting to use code published in ZC 1.5.6 to support strict server mode.
  - Reworked code to better account and control sub-functional area execution.
  - Removed single quotes from SQL queries that included cast integers to minimize further processing.
  - Expanded attribute text switching to more than product priced by attribute such that should apply to
      product that have attributes that affect the base price.
  - Corrected price display and determination to include pricing associated with text (word and/or letter pricing, etc...)
  - Updated javascript side to use exactly equal to instead of loosely comparing two values (in a majority of examples).
  - Switched javascript calls to use dot notation where able.
  - Added a fallback attempt for collecting/setting JSON response if responseJSON is empty but a non-fault responseText
      is provided.
  - Moved javascript variable declaration out of most function calls.
  - Corrected an issue reported by mvstudio of where the expected text to be in front of the priced item would not be
      displayed if the imgLoc variable in the includes/modules/pages/PRODUCT_TYPE/jscript_dynamic_price_updater.php file
      was not set to "replace".  Essentially the result was that pspClass would be blank when sending to ajax.
  - Added change detection and capture of the html element textarea and changed the text detection to an input event
      instead of a keyup event.  This allows capture/detection of pasted content without firing off multiple events for
      the same modification.  Additional testing may reveal the need to incorporate other event listeners. The process
      would be similar as shown for the number case.
  - Updated ajax.php file to prevent known spiders from performing ajax requests.  This is from the ZC 1.5.6 version of
      the file.
  - Moved the status notification from an alert to a console log when zcJS is not used and a 200 status result is not
      received.
*/


$zc150 = (PROJECT_VERSION_MAJOR > 1 || (PROJECT_VERSION_MAJOR == 1 && substr(PROJECT_VERSION_MINOR, 0, 3) >= 5));
if ($zc150) { // continue Zen Cart 1.5.0

    $sort_order = [
        [
            'configuration_group_id' => [
                'value' => $configuration_group_id,
                'type' => 'integer'
            ],
            'configuration_key' => [
                'value' => 'DPU_ATTRIBUTES_MULTI_PRICE_TEXT',
                'type' => 'string'
            ],
            'configuration_title' => [
                'value' => 'Show alternate text for partial selection',
                'type' => 'string'
            ],
            'configuration_value' => [
                'value' => 'start_at_least',
                'type' => 'string'
            ],
            'configuration_description' => [
                'value' => 'When selections are being made that affect the price of the product, what alternate text (if any) should be shown to the customer.<br>For example, when no selections have been made, the ZC "Starting At" text may be displayed.<br>When one selection of many has been made, then the text may be changed to at least this amount indicating that there are selections to be made that could increase the price.<br>Then once all selections have been made as expected/required the text is or should change to something like Your Price:.<br><br><b>Default: start_at_least</b><br><br>start_at_least: display applicable start at or at least text<br>start_at: display start_at text until all selections have been made<br>at_least: once a selection has been made that does not complete selection display the at_least text.',
                'type' => 'string'
            ],
            'date_added' => [
                'value' => 'NOW()',
                'type' => 'noquotestring'
            ],
            'use_function' => [
                'value' => 'NULL',
                'type' => 'noquotestring'
            ],
            'set_function' => [
                'value' => 'zen_cfg_select_option(array(\'none\', \'start_at_least\', \'start_at\', \'at_least\'),',
                'type' => 'string'
            ],
        ],
        [
            'configuration_group_id' => [
                'value' => $configuration_group_id,
                'type' => 'integer'
            ],
            'configuration_key' => [
                'value' => 'DPU_SHOW_OUT_OF_STOCK_IMAGE',
                'type' => 'string'
            ],
            'configuration_title' => [
                'value' => 'Show or update the display of out-of-stock',
                'type' => 'string'
            ],
            'configuration_value' => [
                'value' => 'quantity_replace',
                'type' => 'string'
            ],
            'configuration_description' => [
                'value' => 'Allows display of the current stock status of a product while the customer remains on the product information page and offers control about the ajax update when the product is identified as out-of-stock.<br><br><b>default: quantity_replace</b><br><br>quantity_replace: if incorporated, instead of showing the quantity of product, display DPU_OUT_OF_STOCK_IMAGE.<br>after: display DPU_OUT_OF_STOCK_IMAGE after the quantity display.<br>before: display DPU_OUT_OF_STOCK_IMAGE before the quantity display.<br>price_replace_only: update the price of the product to display DPU_OUT_OF_STOCK_IMAGE',
                'type' => 'string'
            ],
            'date_added' => [
                'value' => 'NOW()',
                'type' => 'noquotestring'
            ],
            'use_function' => [
                'value' => 'NULL',
                'type' => 'noquotestring'
            ],
            'set_function' => [
                'value' => 'zen_cfg_select_option(array(\'quantity_replace\', \'after\', \'before\', \'price_replace_only\'),',
                'type' => 'string'
            ],
        ],
        [
            'configuration_group_id' => [
                'value' => $configuration_group_id,
                'type' => 'integer'
            ],
            'configuration_key' => [
                'value' => 'DPU_PROCESS_ATTRIBUTES',
                'type' => 'string'
            ],
            'configuration_title' => [
                'value' => 'Modify minimum attribute display price',
                'type' => 'string'
            ],
            'configuration_value' => [
                'value' => 'all',
                'type' => 'string'
            ],
            'configuration_description' => [
                'value' => 'On what should the minimum display price be based for product with attributes? <br><br>Only product that are priced by attribute or for all product that have attributes?<br><br><b>Default: all</b>',
                'type' => 'string'
            ],
            'date_added' => [
                'value' => 'NOW()',
                'type' => 'noquotestring'
            ],
            'use_function' => [
                'value' => 'NULL',
                'type' => 'noquotestring'
            ],
            'set_function' => [
                'value' => 'zen_cfg_select_option(array(\'all\', \'priced_by\'),',
                'type' => 'string'
            ],
        ],
        [
            'configuration_group_id' => [
                'value' => $configuration_group_id,
                'type' => 'integer'
            ],
            'configuration_key' => [
                'value' => 'DPU_PRODUCTDETAILSLIST_PRODUCT_INFO_QUANTITY',
                'type' => 'string'
            ],
            'configuration_title' => [
                'value' => 'Where to display the product_quantity',
                'type' => 'string'
            ],
            'configuration_value' => [
                'value' => 'productDetailsList_product_info_quantity',
                'type' => 'string'
            ],
            'configuration_description' => [
                'value' => 'This is the ID where your product quantity is displayed.<br><br><b>default => productDetailsList_product_info_quantity</b>',
                'type' => 'string'
            ],
            'date_added' => [
                'value' => 'NOW()',
                'type' => 'noquotestring'
            ],
            'use_function' => [
                'value' => 'NULL',
                'type' => 'noquotestring'
            ],
            'set_function' => [
                'value' => 'NULL',
                'type' => 'noquotestring'
            ],
        ],
    ];

    $oldcount_sort_sql = "SELECT MAX(sort_order) as max_sort FROM `" . TABLE_CONFIGURATION . "` WHERE configuration_group_id=" . (int)$configuration_group_id;
    $oldcount_sort = $db->Execute($oldcount_sort_sql);

    foreach ($sort_order as $config_key => $config_item) {
        $sql = "INSERT INTO " . TABLE_CONFIGURATION . " (configuration_group_id, configuration_key, configuration_title, configuration_value, configuration_description, sort_order, date_added, use_function, set_function)
          VALUES (:configuration_group_id:, :configuration_key:, :configuration_title:, :configuration_value:, :configuration_description:, :sort_order:, :date_added:, :use_function:, :set_function:)
          ON DUPLICATE KEY UPDATE sort_order = :sort_order:";
        $sql = $db->bindVars($sql, ':configuration_group_id:', $config_item['configuration_group_id']['value'], $config_item['configuration_group_id']['type']);
        $sql = $db->bindVars($sql, ':configuration_key:', $config_item['configuration_key']['value'], $config_item['configuration_key']['type']);
        $sql = $db->bindVars($sql, ':configuration_title:', $config_item['configuration_title']['value'], $config_item['configuration_title']['type']);
        $sql = $db->bindVars($sql, ':configuration_value:', $config_item['configuration_value']['value'], $config_item['configuration_value']['type']);
        $sql = $db->bindVars($sql, ':configuration_description:', $config_item['configuration_description']['value'], $config_item['configuration_description']['type']);
        $sql = $db->bindVars($sql, ':sort_order:', (int)$oldcount_sort->fields['max_sort'] + ((int)$config_key + 1) * 10, 'integer');
        $sql = $db->bindVars($sql, ':date_added:', $config_item['date_added']['value'], $config_item['date_added']['type']);
        $sql = $db->bindVars($sql, ':use_function:', $config_item['use_function']['value'], $config_item['use_function']['type']);
        $sql = $db->bindVars($sql, ':set_function:', $config_item['set_function']['value'], $config_item['set_function']['type']);
        $db->Execute($sql);
    }
        $messageStack->add('Inserted configuration for ' . $module_name . ' (3_2_0)', 'success');
} // END OF VERSION 1.5.x INSTALL
