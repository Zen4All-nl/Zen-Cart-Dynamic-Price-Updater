<?php
/**
 * dynamic price updater sidebox
 *
 * @package templateSystem
 * @copyright 2007 Kuroi Web Design
 * @copyright Portions Copyright 2003-2007 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

  // test if box should display
if (DPU_STATUS === 'true'){

  $sbload = true; // if any of the PHP conditions fail, set to false and prevent any DPU processing
  $sbpid = (!empty($_GET['products_id']) ? (int)$_GET['products_id'] : 0);
  if ($sbpid === 0)
  {
    $sbload = false;
  }
  elseif (STORE_STATUS > 0 || zen_get_products_price_is_call($sbpid) || zen_get_products_price_is_free($sbpid))
  {
    $sbload = false;
  }
  $sbpidp = zen_get_products_display_price($sbpid);
  if (empty($sbpidp)) {
    $sbload = false;
  }

    $show_dynamic_price_updater_sidebox = $sbload === true;

  if ($show_dynamic_price_updater_sidebox === true && ($current_page_base === 'product_info' || $current_page_base === 'product_music_info'))
  {
    require($template->get_template_dir('tpl_dynamic_price_updater_sidebox.php',DIR_WS_TEMPLATE, $current_page_base,'sideboxes'). '/tpl_dynamic_price_updater_sidebox.php');
    $title =  BOX_HEADING_DYNAMIC_PRICE_UPDATER_SIDEBOX;
    $title_link = false;
    require($template->get_template_dir($column_box_default, DIR_WS_TEMPLATE, $current_page_base,'common') . '/' . $column_box_default);
  }
}
