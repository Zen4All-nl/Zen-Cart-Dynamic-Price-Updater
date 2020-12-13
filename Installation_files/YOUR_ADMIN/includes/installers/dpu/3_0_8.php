<?php
/**
 * @package functions
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: mc12345678 thanks to bislewl 6/9/2015
 */
/* 
  V3.0.8, What changed:
  - Added a switch and code to support deactivating the use of currency symbols
      in the sidebox, similar to the display of the base product price.
  - Corrected the use of the notifier to support using an observer to provide
      a prefix for displayed prices.
  - Removed the specific comparison of an attribute being a checkbox for when
      processing attributes to identify the lowest price of the product.  Inclusion
      of the attribute into the base price is controlled through the attributes
      controller and should not be just blindly omitted within this code.
  - Updated the installer including to incorporate the version checker provided
      in the current alpha release of ZC 1.5.6 and to use that code instead of the built in code and
      instead of pulling the recent file into the distribution of the
      plugin (and then have multiple such versions out and about.)
  - Updated the installer to write a debug log if the installer files have been 
      incorrectly placed in the catalog side of the installer.
  - Updated the installer to expect an admin to be logged in, and the page not currently being
      the login page or as a result of selecting the logoff option.
  - Updated code for initial expected changes for PHP 7.2.
  - Corrected the encoding (BOM) of the file that provides the ajax processing.
  - Corrected issue with price being displayed as zero when it should be otherwise displayed.
  - Added the general page information (though not with zen_href_link) and support understanding what
    the customer is looking at at the point of the call.
  - Updated the jscript code for the product_music to match the product_info code.
  - Added template jscript code to attempt to load jquery if it has not previously included or
      loaded to load jquery 1.12.4
*/


$zc150 = ((int)PROJECT_VERSION_MAJOR > 1 || (PROJECT_VERSION_MAJOR === '1' && substr(PROJECT_VERSION_MINOR, 0, 3) >= 5));
if ($zc150) { // continue Zen Cart 1.5.0

$sort_order = array(
                array('configuration_group_id' => array('value' => $configuration_group_id,
                                                   'type' => 'integer'),
                      'configuration_key' => array('value' => 'DPU_SHOW_SIDEBOX_CURRENCY_SYMBOLS',
                                                   'type' => 'string'),
                      'configuration_title' => array('value' => 'Show sidebox currency symbols',
                                                   'type' => 'string'),
                      'configuration_value' => array('value' => 'true',
                                                   'type' => 'string'),
                      'configuration_description' => array('value' => 'Show currency symbols in the sidebox (when displayed).<br /><br />Default: true',
                                                   'type' => 'string'),
                      'date_added' => array('value' => 'NOW()',
                                                   'type' => 'noquotestring'),
                      'use_function' => array('value' => 'NULL',
                                                   'type' => 'noquotestring'),
                      'set_function' => array('value' => 'zen_cfg_select_option(array(\'true\', \'false\'),',
                                                   'type' => 'string'),
                      ),
                );

    $oldcount_sort_sql = "SELECT MAX(sort_order) as max_sort FROM `". TABLE_CONFIGURATION ."` WHERE configuration_group_id=" . (int)$configuration_group_id;
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

} // END OF VERSION 1.5.x INSTALL
