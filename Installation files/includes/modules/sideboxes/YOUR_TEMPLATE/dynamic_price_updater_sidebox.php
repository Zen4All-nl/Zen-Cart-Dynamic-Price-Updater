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
if (DPU_STATUS == 'true'){
  $show_dynamic_price_updater_sidebox = true;

  if (($current_page_base == ('product_info') && $show_dynamic_price_updater_sidebox == true) or ($current_page_base == ('product_music_info') && $show_dynamic_price_updater_sidebox == true))
  {
    require($template->get_template_dir('tpl_dynamic_price_updater_sidebox.php',DIR_WS_TEMPLATE, $current_page_base,'sideboxes'). '/tpl_dynamic_price_updater_sidebox.php');
    $title =  BOX_HEADING_DYNAMIC_PRICE_UPDATER_SIDEBOX;
    $title_link = false;
    require($template->get_template_dir($column_box_default, DIR_WS_TEMPLATE, $current_page_base,'common') . '/' . $column_box_default);
  }
}
?>