<?php
/**
* @package Dynamic Price Updater
* @author Design75
* @version 3.0
* @licence This module is released under the GNU/GPL licence
*/

define('BOX_HEADING_DYNAMIC_PRICE_UPDATER_SIDEBOX', 'Price Breakdown'); // the heading that shows in the Updater sidebox
define('DPU_BASE_PRICE', 'Base price');

define('UPDATER_PREFIX_TEXT', 'Your price: ');
define('UPDATER_PREFIX_TEXT_STARTING_AT', 'Starting at: ');
define('UPDATER_PREFIX_TEXT_AT_LEAST', 'At least: ');
define('DPU_SHOW_QUANTITY_FRAME', '&nbsp;(%s)');
define('DPU_SIDEBOX_QUANTITY_FRAME', '&nbsp;x&nbsp;%s'); // how the weight is displayed in the sidebox.  Default is ' x 1'... set to '' for no display... %s is the quantity itself
define('DPU_SIDEBOX_PRICE_FRAME', '&nbsp;(%s)'); // how the attribute price is displayed
define('DPU_SIDEBOX_TOTAL_FRAME', '<span class="DPUSideboxTotalText">Total: </span><span class="DPUSideboxTotalDisplay">%s</span>'); // this is how the total should be displayed.  %s is the price itself as displayed in the
define('DPU_SIDEBOX_FRAME', '<span class="DPUSideBoxName">%1$s</span>%3$s%2$s<br />'); // the template for the sidebox display.  Instructions below
/*
 *DPU_SIDEBOX_FRAME has 3 variables you can use... They are:
 * %1$s - The attribute name
 * %2$s - The quantity display
 * %3$s - The individual price display
 * You can position these anywhere around the DPU_SIDEBOX_FRAME string or even remove them to prevent them from displaying
 */