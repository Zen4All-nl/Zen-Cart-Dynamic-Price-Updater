<?php
$failed = false;

$dpu_menu_title = 'Dynamic Price Updater';
$dpu_menu_text = 'Settings for Dynamic Price Updater';

/* find if Dynamic Price Updater Configuration Group Exists */
$sql = "SELECT * FROM ".TABLE_CONFIGURATION_GROUP." WHERE configuration_group_title = '".$dpu_menu_title."'";
$original_config = $db->Execute($sql);

if(!$original_config->EOF)
{
	// if exists updating the existing Dynamic Price Updater configuration group entry
	$sql = "UPDATE ".TABLE_CONFIGURATION_GROUP." SET
		configuration_group_description = '".$dpu_menu_text."'
		WHERE configuration_group_title = '".$dpu_menu_title."'";
	$db->Execute($sql);
	$sort = $original_config->fields['sort_order'];

}
else {
	/* Find max sort order in the configuation group table */
	$sort_query = "SELECT MAX(sort_order) as max_sort FROM `".TABLE_CONFIGURATION_GROUP."`";
	$max_sort = $db->Execute($sort_query);
	if(!$max_sort->EOF) {
		$max_sort = $max_sort->fields['max_sort'] + 1;

		/* Create Dynamic Price Updater configuration group */
		$sql = "INSERT INTO ".TABLE_CONFIGURATION_GROUP." (configuration_group_id, configuration_group_title, configuration_group_description, sort_order, visible) VALUES (NULL, '".$dpu_menu_title."', '".$dpu_menu_text."', ".$max_sort.", '1')";
		$db->Execute($sql);
	}
	else {
		$messageStack->add('Database Error: Unable to access sort_order in table' . TABLE_CONFIGURATION_GROUP, 'error');
		$failed = true;
	}
}

/* Find configuation group ID of Dynamic Price Updater */
$sql = "SELECT configuration_group_id FROM ".TABLE_CONFIGURATION_GROUP." WHERE configuration_group_title='".$dpu_menu_title."' LIMIT 1";
$result = $db->Execute($sql);
if(!$result->EOF) {
	$dpu_configuration_id = $result->fields['configuration_group_id'];

	/* Remove Dynamic Price Updater items from the configuration table */
	$sql = "DELETE FROM ".DB_PREFIX."configuration WHERE configuration_group_id ='".$dpu_configuration_id."'";
	$db->Execute($sql);

	//-- DYNAMIC PRICE UPDATER VERSION
  $sql = "INSERT INTO ".DB_PREFIX."configuration VALUES (NULL, 'Dynamic Price Updater Status', 'DPU_STATUS', 'false', 'Enable Dynamic Price Updater?', '".$dpu_configuration_id."', 10, NOW(), NOW(), NULL, 'zen_cfg_select_option(array(''true'', ''false''),')";
  $db->Execute($sql);
  $sql = "INSERT INTO ".DB_PREFIX."configuration VALUES (NULL, 'Dynamic Price Updater Version', 'DPU_VERSION', '3.0', 'Dynamic Price Updater version', '".$dpu_configuration_id."', 20, NULL, now(), NULL, 'trim(')";
  $db->Execute($sql);
  $sql = "INSERT INTO ".DB_PREFIX."configuration VALUES (NULL, 'Where to display the price','DPU_PRICE_ELEMENT_ID', 'productPrices', 'this is the ID of the element where your price is displayed', '".$dpu_configuration_id."', 30, NULL, now(), NULL, NULL)";
  $db->Execute($sql);
  $sql = "INSERT INTO ".DB_PREFIX."configuration VALUES (NULL, '','DPU_PRODUCT_FORM', 'cart_quantity', 'this should never change', '".$dpu_configuration_id."', 40, NULL, now(), NULL, NULL)";
  $db->Execute($sql);
  $sql = "INSERT INTO ".DB_PREFIX."configuration VALUES (NULL, 'Where to display the weight','DPU_WEIGHT_ELEMENT_ID', 'productWeight', 'this is the ID where your weight is displayed', '".$dpu_configuration_id."', 50, NULL, now(), NULL, NULL)";
  $db->Execute($sql);
  $sql = "INSERT INTO ".DB_PREFIX."configuration VALUES (NULL, 'show a small loading graphic','DPU_SHOW_LOADING_IMAGE', 'true', 'true to show a small loading graphic so the user knows something is happening', '".$dpu_configuration_id."', 60, NULL, now(), NULL, 'zen_cfg_select_option(array(''true'', ''false''),')";
  $db->Execute($sql);
  $sql = "INSERT INTO ".DB_PREFIX."configuration VALUES (NULL, 'Show currency symbols','DPU_SHOW_CURRENCY_SYMBOLS', 'true', '', '".$dpu_configuration_id."', 70, NULL, now(), NULL, 'zen_cfg_select_option(array(''true'', ''false''),')";
  $db->Execute($sql);
  $sql = "INSERT INTO ".DB_PREFIX."configuration VALUES (NULL, 'Show product quantity','DPU_SHOW_QUANTITY', 'false', '', '".$dpu_configuration_id."', 80, NULL, now(), NULL, 'zen_cfg_select_option(array(''true'', ''false''),')";
  $db->Execute($sql);
  $sql = "INSERT INTO ".DB_PREFIX."configuration VALUES (NULL, 'Where to display the second price','DPU_SECOND_PRICE', 'cartAdd', '', '".$dpu_configuration_id."', 90, NULL, now(), NULL, NULL)";
  $db->Execute($sql);
}
else {
	$messageStack->add('Database Error: Unable to access configuration_group_id in table' . TABLE_CONFIGURATION_GROUP, 'error');
	$failed = true;
}

// Add support for admin profiles to edit configuration and orders
if(function_exists('zen_register_admin_page')) {
	if(!zen_page_key_exists('configDynamicPriceUpdater')) {
		// Get the sort order
		$page_sort_query = "SELECT MAX(sort_order) as max_sort FROM `". TABLE_ADMIN_PAGES ."` WHERE menu_key='configuration'";
		$page_sort = $db->Execute($page_sort_query);
		$page_sort = $page_sort->fields['max_sort'] + 1;

		// Register the administrative pages
		zen_register_admin_page('configDynamicPriceUpdater', 'BOX_CONFIGURATION_DYNAMIC_PRICE_UPDATER',
			'FILENAME_CONFIGURATION', 'gID=' . $dpu_configuration_id,
			'configuration', 'Y', $page_sort);
	}
}

if(file_exists(DIR_FS_ADMIN . DIR_WS_INCLUDES . 'auto_loaders/config.dpu.php'))
{
	if(!unlink(DIR_FS_ADMIN . DIR_WS_INCLUDES . 'auto_loaders/config.dpu.php'))
	{
		$messageStack->add('The auto-loader file '.DIR_FS_ADMIN.'includes/auto_loaders/config.dpu.php has not been deleted. For this module to work you must delete the '.DIR_FS_ADMIN.'includes/auto_loaders/config.dpu.php file manually.  Before you post on the Zen Cart forum to ask, YES you are REALLY supposed to follow these instructions and delete the '.DIR_FS_ADMIN.'includes/auto_loaders/config.dpu.php file.','error');
		$failed = true;
	}
}

if(!$failed) $messageStack->add('Dynamic Price Updater v3.0 install completed!','success');